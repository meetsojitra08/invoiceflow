<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicInvoiceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('i/{token}', [PublicInvoiceController::class, 'show'])->name('invoices.public');

Route::middleware(['auth'])->group(function () {
    Route::post('companies/switch', CompanySwitchController::class)->name('companies.switch');
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::post('clients/{id}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class)->except(['show']);

    Route::post('invoices/calculate', [InvoiceController::class, 'calculate'])->name('invoices.calculate');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::resource('invoices', InvoiceController::class);
});

require __DIR__.'/settings.php';
