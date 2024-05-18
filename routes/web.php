<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('signed')->name('invoices.show');
