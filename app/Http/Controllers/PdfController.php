<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Edition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PdfController extends Controller
{
    public function clients()
    {
        $clients = Client::with(['contacts', 'invoices', 'documents', 'category', 'provisionElements.provision'])->get();

        return view('pdf.clients', ['clients' => $clients]);
    }

    public function client(Client $client)
    {
        $client = $client->load(['contacts', 'invoices', 'documents', 'category', 'provisionElements.provision']);
        $edition = Edition::find(setting('edition_id'));

        $view = View::make('pdf.client', ['client' => $client, 'edition' => $edition]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption(['defaultFont' => 'sans-serif'])
            ->stream(str($client->name)->slug().'.pdf');

        return $pdf;
    }
}
