<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Middleware\SetCurrentCompany;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public Token Exchange
    Route::post('auth/token', [AuthController::class, 'token'])->name('api.v1.auth.token');

    // Authenticated API routes with rate limit 60/min and tenant context
    Route::middleware(['auth:sanctum', SetCurrentCompany::class, 'throttle:60,1'])->group(function (): void {
        Route::delete('auth/token', [AuthController::class, 'revoke'])->name('api.v1.auth.revoke');

        // Clients
        Route::apiResource('clients', ClientController::class)->names('api.v1.clients');

        // Products
        Route::apiResource('products', ProductController::class)->names('api.v1.products');

        // Invoices
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('api.v1.invoices.pdf');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('api.v1.invoices.send');
        Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('api.v1.invoices.void');
        Route::apiResource('invoices', InvoiceController::class)->names('api.v1.invoices');

        // Payments
        Route::get('invoices/{invoice}/payments', [PaymentController::class, 'index'])->name('api.v1.invoices.payments.index');
        Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('api.v1.invoices.payments.store');
    });
});
