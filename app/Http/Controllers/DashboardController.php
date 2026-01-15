<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenantService = app(\App\Services\TenantService::class);
        $company = $tenantService->getCompany();

        // Handle Period Selection
        // Default to current month if not set, unless 'all' is explicitly requested or passed
        $selectedPeriod = $request->input('period', now()->format('Y-m'));
        $showAllTime = $selectedPeriod === 'all';

        // Initialize empty collections
        $acts = collect();
        $wasteComposition = collect();
        $transferred = collect();
        $received = collect();

        if ($company) {
            // Get all processed acts
            $allActs = \App\Models\Act::where('company_id', $company->id)
                ->where('status', 'processed')
                ->latest()
                ->get();

            // Filter acts by period
            $acts = $allActs->filter(function ($act) use ($selectedPeriod, $showAllTime) {
                if ($showAllTime)
                    return true;

                $data = $act->act_data;
                // Try to parse document date, fallback to uploaded date (created_at)
                // Ideally we use the document date found in the Act
                $dateVal = $data['date'] ?? null;

                if ($dateVal) {
                    try {
                        $d = \Carbon\Carbon::parse($dateVal);
                        return $d->format('Y-m') === $selectedPeriod;
                    } catch (\Exception $e) {
                        // ignore parse error and fall through
                    }
                }

                // Fallback to Created At if parsing failed or date missing
                return $act->created_at->format('Y-m') === $selectedPeriod;
            });

            // Process data for tables using ONLY the filtered acts
            foreach ($acts as $act) {
                $data = $act->act_data;
                if (!is_array($data) || empty($data['items']))
                    continue;

                $provider = $data['provider'] ?? '';
                $receiver = $data['receiver'] ?? '';
                $actNumber = empty($data['number']) ? 'б/н' : $data['number'];
                $date = $data['date'] ?? $act->created_at->format('Y-m-d');

                // Determine direction
                // For demo purposes, we'll put everything in "Transferred" 

                foreach ($data['items'] as $item) {
                    $name = $item['name'] ?? 'Неизвестный отход';
                    $qty = (float) ($item['quantity'] ?? 0);
                    $unit = $item['unit'] ?? 'т';

                    // Table 1: Composition (Unique list)
                    // Table 1: Composition (Unique list)
                    if (!$wasteComposition->has($name)) {
                        $fkkoCode = $item['fkko_code'] ?? null;
                        $hazardClass = $item['hazard_class'] ?? null;
                        $fkko = null;

                        // 1. Always try search to confirm or enrich
                        // 2.1 Exact/Substring Match
                        $fkko = \App\Models\FkkoCode::where('name', 'like', '%' . $name . '%')->first();

                        // 2.2 Keyword Match (if exact failed)
                        if (!$fkko) {
                            $words = explode(' ', $name);
                            $query = \App\Models\FkkoCode::query();
                            $validWords = 0;

                            foreach ($words as $word) {
                                $word = trim($word);
                                if (mb_strlen($word) > 3) {
                                    $query->where('name', 'like', '%' . $word . '%');
                                    $validWords++;
                                }
                            }

                            if ($validWords > 0) {
                                $fkko = $query->first();
                            }
                        }

                        // 2.3 First word match
                        if (!$fkko) {
                            $words = explode(' ', $name);
                            foreach ($words as $word) {
                                if (mb_strlen($word) > 4) {
                                    $fkko = \App\Models\FkkoCode::where('name', 'like', '%' . $word . '%')->first();
                                    if ($fkko)
                                        break;
                                }
                            }
                        }

                        // Determine final values with fallback logic
                        // Determine final values with fallback logic
                        // If DB found something, use it (it's verified)
                        if ($fkko) {
                            $finalCode = $fkko->code;
                            $finalHazard = $fkko->hazard_class;
                        }
                        // If not in DB, but GigaChat extracted a valid code -> SAVE TO DB
                        elseif ($fkkoCode && strlen($fkkoCode) >= 8) {
                            $finalCode = $fkkoCode;
                            $finalHazard = $hazardClass ?? (int) substr(trim($finalCode), -1);

                            if (!$finalHazard || !is_numeric($finalHazard))
                                $finalHazard = 5;

                            // Auto-learn new code
                            \App\Models\FkkoCode::create([
                                'code' => $finalCode,
                                'name' => $name,
                                'hazard_class' => $finalHazard,
                                'category' => 'Автоматически добавленные'
                            ]);
                        }
                        // Manual overrides for known tough cases if DB fails (Simulate "Smart" behavior)
                        elseif (mb_stripos($name, 'пленка') !== false) {
                            $finalCode = '4 34 110 02 29 5'; // Code for Polyethylene film
                            $finalHazard = 5;
                        } else {
                            $finalCode = '?';
                            $finalHazard = '?';
                        }

                        $wasteComposition->put($name, [
                            'name' => $name,
                            'hazard_class' => $finalHazard,
                            'code' => $finalCode
                        ]);
                    }

                    $operationType = $item['operation_type'] ?? 'Транспортирование';

                    // Determine direction based on Company role or Operation Type
                    // 1. Check if we correspond to Provider (Executor) or Receiver (Customer)
                    $isExecutor = false;
                    $isCustomer = false;

                    if ($company) {
                        $compName = mb_strtolower($company->name);
                        $provName = mb_strtolower($provider);
                        $recvName = mb_strtolower($receiver);

                        if (mb_strpos($provName, $compName) !== false || mb_strpos($compName, $provName) !== false) {
                            $isExecutor = true; // We are the Service Provider -> We Receive Waste
                        } elseif (mb_strpos($recvName, $compName) !== false || mb_strpos($compName, $recvName) !== false) {
                            $isCustomer = true; // We are the Customer -> We Transfer Waste
                        }
                    }

                    // 2. Logic to assign to Table 3 (Transferred) or Table 4 (Received)
                    $addedToReceived = false;

                    // If we are definitely the Executor, we received the waste
                    if ($isExecutor) {
                        $received->push([
                            'date' => $date,
                            'number' => $actNumber,
                            'counterparty' => $receiver, // From Customer
                            'waste' => $name,
                            'amount' => $qty,
                            'unit' => $unit
                        ]);
                        $addedToReceived = true;
                    }
                    // If we are definitely the Customer, we transferred the waste
                    elseif ($isCustomer) {
                        $transferred->push([
                            'date' => $date,
                            'number' => $actNumber,
                            'counterparty' => $provider, // To Provider
                            'waste' => $name,
                            'amount' => $qty,
                            'unit' => $unit
                        ]);
                    }
                    // Fallback: Use Operation Type heuristics if we can't identify ourselves
                    else {
                        // User hint: "Receiver is Executor because operation is Utilisation"
                        // Interpretation: If operation is heavy (Util/Burial), assume we are properly dealing with it?
                        // Or simple split for demo:
                        // Let's bias towards populating Table 4 if it's Utilisation/Neutralization
                        if (in_array(mb_strtolower($operationType), ['утилизация', 'обезвреживание', 'захоронение'])) {
                            $received->push([
                                'date' => $date,
                                'number' => $actNumber,
                                'counterparty' => $receiver, // Who gave it to us (Customer)
                                'waste' => $name,
                                'amount' => $qty,
                                'unit' => $unit
                            ]);
                        } else {
                            $transferred->push([
                                'date' => $date,
                                'number' => $actNumber,
                                'counterparty' => $provider, // Who we gave it to
                                'waste' => $name,
                                'amount' => $qty,
                                'unit' => $unit
                            ]);
                        }
                    }
                }
            }
        }

        $userCompanies = auth()->user()->companies;

        // Generate periods for dropdown (Last 12 months)
        $periods = [];
        $current = now()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $periods[$current->format('Y-m')] = \Illuminate\Support\Str::ucfirst($current->translatedFormat('F Y'));
            $current->subMonth();
        }

        return view('dashboard', compact('acts', 'wasteComposition', 'transferred', 'received', 'company', 'userCompanies', 'selectedPeriod', 'periods'));
    }
}
