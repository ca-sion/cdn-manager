<?php

use App\Livewire\AdvertiserForm;
use App\Livewire\FrontEditClient;
use App\Livewire\FrontListClients;
use App\Livewire\FrontListProvisions;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use ElicDev\SiteProtection\Http\Middleware\SiteProtection;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('signed')->name('invoices.show');

Route::get('/pdf/provisions', [PdfController::class, 'provisions'])->middleware(SiteProtection::class)->name('pdf.provisions');
Route::get('/pdf/clients', [PdfController::class, 'clients'])->middleware(SiteProtection::class)->name('pdf.clients');
Route::get('/pdf/client/{client}', [PdfController::class, 'client'])->middleware('signed')->name('pdf.client');

Route::get('provisions', FrontListProvisions::class)->middleware(SiteProtection::class)->name('front.provisions');
Route::get('clients', FrontListClients::class)->middleware(SiteProtection::class)->name('front.clients');
Route::get('clients/{record}', FrontEditClient::class)->name('front.client');

Route::get('advertisers/form', AdvertiserForm::class)->name('advertisers.form');
Route::get('advertisers/success', [MessageController::class, 'adertiserSuccess'])->middleware('signed')->name('advertisers.success');
