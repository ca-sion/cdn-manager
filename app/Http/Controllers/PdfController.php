<?php

namespace App\Http\Controllers;

use App\Models\Client;

class PdfController extends Controller
{
    public function clients()
    {
        $clients = Client::with(['contacts', 'invoices', 'documents', 'category', 'provisionElements.provision'])->get();

        return view('pdf.clients', ['clients' => $clients]);
    }
}
