<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $referralCount = $user->referrals()->count();
        $referralEarnings = $user->referralEarnings()->with('referral')->latest()->take(10)->get();
        $referralPayouts = $user->referralPayouts()->latest()->take(10)->get();

        return view('profile.index', compact('user', 'referralCount', 'referralEarnings', 'referralPayouts'));
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'payment_method' => 'required|string',
            'payment_details' => 'required|string',
        ]);

        $user = auth()->user();
        $amount = (float) $request->amount;

        if ($user->referral_balance < $amount) {
            return back()->with('error', 'Недостаточно средств на балансе.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $amount, $request) {

            $user->referralPayouts()->create([
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'payment_details' => $request->payment_details,
            ]);
            $user->decrement('referral_balance', $amount);
        });

        return back()->with('success', 'Заявка на вывод успешно создана.');
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
        ], [
            'phone.unique' => 'Этот номер телефона уже привязан к другому аккаунту.',
        ]);

        $user->update([
            'phone' => $request->phone,
        ]);

        return back()->with('success', 'Профиль успешно обновлен.');
    }
}
