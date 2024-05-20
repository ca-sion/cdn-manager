<?php

use App\Livewire\FrontListClients;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\InvoiceController;
use App\Livewire\AdvertiserForm;
use ElicDev\SiteProtection\Http\Middleware\SiteProtection;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('signed')->name('invoices.show');

Route::get('/pdf/clients', [PdfController::class, 'clients'])->name('pdf.clients');

Route::get('clients', FrontListClients::class)->middleware(SiteProtection::class)->name('front.clients');
Route::get('advertisers/form', AdvertiserForm::class)->name('advertisers.form');
