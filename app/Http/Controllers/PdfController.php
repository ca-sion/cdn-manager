<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCategory;
use App\Models\Edition;
use App\Models\Provision;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;

class PdfController extends Controller
{
    public function clients()
    {
        $displayAmount = (bool) request()->input('amount');
        $displayContacts = request()->input('contacts');

        $category = request()->input('category');
        $provision = request()->input('provision');
        $provisions = request()->input('provisions', []);

        $clients = Client::with(['contacts', 'invoices', 'documents', 'category', 'provisionElements.provision'])
        ->when($category, function (Builder $query, int $category) {
            $query->where('category_id', $category);
        })
        ->when($provision, function (Builder $query, int $provision) {
            $query->whereRelation('provisionElements', 'id', '=', $provision);
        })
        ->when($provisions, function (Builder $query, array $provisions) {
            $query->whereRelation('provisionElements', 'id', '=', $provisions);
        })
        ->get();

        // Form
        $clientCategories = ClientCategory::all();
        $provisions = Provision::all();

        return view('pdf.clients', compact('clients', 'displayAmount', 'displayContacts', 'clientCategories', 'category', 'provision', 'provisions'));
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
