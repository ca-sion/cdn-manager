<?php

use App\Livewire\DonorForm;
use App\Livewire\VipResponse;
use App\Livewire\AdvertiserForm;
use App\Livewire\AdvertiserStart;
use App\Livewire\FrontEditClient;
use App\Livewire\FrontListClients;
use App\Livewire\FrontListProvisions;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\TrackingController;
use ElicDev\SiteProtection\Http\Middleware\SiteProtection;

use App\Livewire\FrontRunRegistration;

Route::get('/', function () {
    return view('welcome');
});

Route::get('registrations/{type}', FrontRunRegistration::class)->name('front.run-registration.create');
Route::get('registrations/{type}/{registration}', FrontRunRegistration::class)->middleware('signed')->name('front.run-registration.edit');

Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('signed')->name('invoices.show');
Route::get('/invoices/{invoice}/eml', [InvoiceController::class, 'eml'])->middleware('signed')->name('invoices.eml');
Route::get('/invoices/{invoice}/emlRelaunch', [InvoiceController::class, 'emlRelaunch'])->middleware('signed')->name('invoices.emlRelaunch');

Route::get('/pdf/provisions', [PdfController::class, 'provisions'])->middleware(SiteProtection::class)->name('pdf.provisions');
Route::get('/pdf/clients', [PdfController::class, 'clients'])->middleware(SiteProtection::class)->name('pdf.clients');
Route::get('/pdf/client/{client}', [PdfController::class, 'client'])->middleware('signed')->name('pdf.client');

Route::get('provisions', FrontListProvisions::class)->middleware(SiteProtection::class)->name('front.provisions');
Route::get('clients', FrontListClients::class)->middleware(SiteProtection::class)->name('front.clients');
Route::get('clients/{record}', FrontEditClient::class)->name('front.client');

Route::get('advertisers/start', AdvertiserStart::class)->name('advertisers.start');
Route::get('advertisers/form', AdvertiserForm::class)->name('advertisers.form');
Route::get('advertisers/form/{client}', AdvertiserForm::class)->middleware('signed')->name('advertisers.form.client');
Route::get('advertisers/success', [MessageController::class, 'advertiserSuccess'])->middleware('signed')->name('advertisers.success');

Route::get('donors/form', DonorForm::class)->name('donors.form');
Route::get('donors/form/{contact}', DonorForm::class)->middleware('signed')->name('donors.form.contact');
Route::get('donors/success', [MessageController::class, 'donorSuccess'])->middleware('signed')->name('donors.success');

Route::get('vip/response/{provisionElement}', VipResponse::class)->name('vip.response')->middleware('signed');

Route::get('/track/engagements/{engagement}', [TrackingController::class, 'engagement'])->name('track.engagement')->middleware('signed');

Route::prefix('reports')->middleware([SiteProtection::class])->group(function () {
    Route::get('advertisers', [ReportsController::class, 'advertisers'])->name('reports.advertisers');
    Route::get('donors', [ReportsController::class, 'donors'])->name('reports.donors');
    Route::get('interlcass-donors', [ReportsController::class, 'interclassDonors'])->name('reports.interclass-donors');
    Route::get('journal-provisions', [ReportsController::class, 'journalProvisions'])->name('reports.journal-provisions');
    Route::get('client-provisions', [ReportsController::class, 'clientProvisions'])->name('reports.client-provisions');
    Route::get('provisions-comparison', [ReportsController::class, 'provisionsComparison'])->name('reports.provisions-comparison');
    Route::get('vip', [ReportsController::class, 'vip'])->name('reports.vip');
    Route::get('banners', [ReportsController::class, 'banners'])->name('reports.banners');
    Route::get('screens', [ReportsController::class, 'screens'])->name('reports.screens');
});
