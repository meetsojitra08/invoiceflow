<?php

use App\Http\Controllers\CompanySwitchController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('companies/switch', CompanySwitchController::class)->name('companies.switch');
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
