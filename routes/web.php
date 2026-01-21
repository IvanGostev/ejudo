<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {
    // Login & Register Pages
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // We act as if login/register is same flow for SMS? Or separate? 
    // Usually SMS auth unifies them, but let's keep separate methods if needed.
    // For now, let's say /login is the main entry.

    // API/Actions
    Route::post('/auth/send-code', [AuthController::class, 'sendCode'])->name('auth.send-code');
    Route::post('/auth/verify-code', [AuthController::class, 'verifyCode'])->name('auth.verify-code');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::middleware('tenant')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('companies', \App\Http\Controllers\CompanyController::class);
        Route::post('companies/{company}/switch', [\App\Http\Controllers\CompanyController::class, 'switch'])->name('companies.switch');

        Route::get('/journal/initial-balance', [\App\Http\Controllers\JournalController::class, 'createInitialBalance'])->name('journal.initial-balance.create');
        Route::post('/journal/initial-balance', [\App\Http\Controllers\JournalController::class, 'storeInitialBalance'])->name('journal.initial-balance.store');

        Route::get('/journal/{journal}/download', [\App\Http\Controllers\JournalController::class, 'download'])->name('journal.download');
        Route::get('/journal/{journal}/download-pdf', [\App\Http\Controllers\JournalController::class, 'downloadPdf'])->name('journal.download-pdf');

        Route::resource('acts', \App\Http\Controllers\ActController::class);
        Route::resource('journal', \App\Http\Controllers\JournalController::class);

        Route::delete('/acts/{act}/item/{itemIndex}', [\App\Http\Controllers\ActController::class, 'destroyItem'])->name('acts.destroy-item');

        Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
        Route::get('/instruction', [\App\Http\Controllers\InstructionController::class, 'index'])->name('instruction.index');
        Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'index'])->name('subscription.index');
        Route::post('/subscription/create', [\App\Http\Controllers\SubscriptionController::class, 'create'])->name('subscription.create');
        Route::get('/subscription/callback', [\App\Http\Controllers\SubscriptionController::class, 'callback'])->name('subscription.callback');
        Route::get('/success', [\App\Http\Controllers\SubscriptionController::class, 'success'])->name('payment.success');
        Route::any('/notification', [\App\Http\Controllers\SubscriptionController::class, 'webhook'])->name('subscription.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
        Route::get('/fkko/search', [\App\Http\Controllers\FkkoController::class, 'search'])->name('fkko.search');
        Route::get('/fkko', function () {
            return "FKKO Reference (Placeholder)";
        })->name('fkko.index');

        Route::post('/role/set', [\App\Http\Controllers\RoleController::class, 'setRole'])->name('role.set');
        Route::get('/acts/manual/create', [\App\Http\Controllers\ManualActController::class, 'create'])->name('acts.manual.create');
        Route::post('/acts/manual/store', [\App\Http\Controllers\ManualActController::class, 'store'])->name('acts.manual.store');

    });

    Route::get('/company/create', [AuthController::class, 'showCompanyCreate'])->name('company.create');
    Route::post('/company', [AuthController::class, 'registerCompany'])->name('company.store');
});

// Dynamic Pages (Catch-all for pages like /about, /offer)
Route::get('/{slug}', [\App\Http\Controllers\PageController::class, 'show'])->name('page.show');
