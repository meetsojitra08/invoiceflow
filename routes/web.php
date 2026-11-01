<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySwitchController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::get('i/{token}', [PublicInvoiceController::class, 'show'])->name('invoices.public');
Route::get('i/{token}/pdf', [PublicInvoiceController::class, 'downloadPdf'])->name('invoices.public.pdf');
Route::get('invitations/{token}/accept', [InvitationController::class, 'show'])->name('invitations.accept');
Route::post('invitations/{token}/accept', [InvitationController::class, 'accept'])->name('invitations.accept.post');

Route::middleware(['auth'])->group(function () {
    Route::post('companies/switch', CompanySwitchController::class)->name('companies.switch');
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team/invite', [TeamController::class, 'invite'])->name('team.invite');
    Route::put('team/members/{member}', [TeamController::class, 'updateRole'])->name('team.members.update');
    Route::delete('team/members/{member}', [TeamController::class, 'removeMember'])->name('team.members.remove');

    Route::post('clients/{id}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::resource('clients', ClientController::class);
    Route::resource('products', ProductController::class)->except(['show']);

    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/calculate', [InvoiceController::class, 'calculate'])->name('invoices.calculate');
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate');
    Route::resource('invoices', InvoiceController::class);
});

require __DIR__.'/settings.php';
