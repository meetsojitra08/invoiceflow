<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::post('webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('i/{token}', [PublicInvoiceController::class, 'show'])->name('invoices.public');
Route::get('i/{token}/pdf', [PublicInvoiceController::class, 'downloadPdf'])->name('invoices.public.pdf');
Route::post('i/{token}/checkout', [PublicInvoiceController::class, 'checkout'])->name('invoices.public.checkout');
Route::get('i/{token}/payment-success', [PublicInvoiceController::class, 'paymentSuccess'])->name('invoices.public.success');
Route::get('invitations/{token}/accept', [InvitationController::class, 'show'])->name('invitations.accept');
Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept.post');

Route::middleware(['auth'])->group(function () {
    Route::post('companies/switch', CompanySwitchController::class)->name('companies.switch');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export/invoices', [ReportController::class, 'exportInvoices'])->name('reports.export.invoices');
    Route::get('reports/export/payments', [ReportController::class, 'exportPayments'])->name('reports.export.payments');

    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::put('team/members/{member}', [TeamController::class, 'updateRole'])->name('team.members.update');
    Route::delete('team/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.remove');

    Route::post('clients/{id}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('invoices/{invoice}/payments', [PaymentController::class, 'store'])->name('invoices.payments.store');

    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/calculate', [InvoiceController::class, 'calculate'])->name('invoices.calculate');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::resource('invoices', InvoiceController::class);
});

require __DIR__.'/settings.php';
