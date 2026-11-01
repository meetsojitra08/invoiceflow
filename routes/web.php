<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::post('companies/switch', CompanySwitchController::class)->name('companies.switch');
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::post('clients/{id}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class)->except(['show']);
});

require __DIR__.'/settings.php';
