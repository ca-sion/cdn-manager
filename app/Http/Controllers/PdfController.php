<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ClientCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProvisionElement;
use App\Models\ProvisionCategory;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PdfController extends Controller
{
    public function clients()
    {
        $displayAmount = (bool) request()->input('amount');
        $displayContacts = request()->input('contacts');
        $displayProvisions = request()->input('provisions');

        $categoryId = request()->input('category');
        $provisionId = request()->input('provision');
        $provisionCategoryId = request()->input('provision_category');

        $provisions = request()->input('provisions', []);

        $clients = Client::with(['contacts', 'invoices', 'documents', 'category', 'provisionElements.provision'])
            ->when($categoryId, function (Builder $query, int $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($provisionId, function (Builder $query, int $provisionId) {
                $query->whereRelation('provisionElements', 'provision_id', $provisionId);
            })
            ->when($provisionCategoryId, function (Builder $query, int $provisionCategoryId) {
                $query->whereRelation('provisionElements.provision', 'category_id', $provisionCategoryId);
            })
            ->get();

        // Aggragates
        $amountSum = $clients->sum(function ($client) {
            return $client->currentProvisionElementsAmount();
        });
        $netAmountSum = $clients->sum(function ($client) {
            return $client->currentProvisionElementsNetAmount();
        });

        // Form
        $clientCategories = ClientCategory::all();
        $provisions = Provision::all();
        $provisionCategories = ProvisionCategory::all();

        return view('pdf.clients', compact('clients', 'provisions', 'displayAmount', 'displayContacts', 'displayProvisions', 'amountSum', 'netAmountSum', 'clientCategories', 'provisionCategories', 'categoryId', 'provisionId', 'provisionCategoryId'));
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

    public function provisions()
    {
        $displayAmount = (bool) request()->input('amount');
        $displayClient = request()->input('client');

        $clientCategoryId = request()->input('client_category');
        $provisionCategoryId = request()->input('provision_category');
        $provisionId = request()->input('provision');

        $provisions = ProvisionElement::with(['recipient' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                Contact::class => ['provisionElements'],
                Client::class  => ['provisionElements'],
            ]);
        }, 'provision', 'provision.category'])
            ->when($provisionId, function (Builder $query, int $provisionId) {
                $query->where('provision_id', $provisionId);
            })
            ->when($provisionCategoryId, function (Builder $query, int $provisionCategoryId) {
                $query->whereRelation('provision.category', 'id', $provisionCategoryId);
            })
            ->get();

        // Filter
        if ($clientCategoryId) {
            $provisions = $provisions->where('recipient.category_id', $clientCategoryId);
        }

        // Aggragates
        $amountSum = $provisions->sum(function ($provision) {
            return $provision->price->amount;
        });
        $netAmountSum = $provisions->sum(function ($provision) {
            return $provision->price->net_amount;
        });

        // Form
        $clientCategories = ClientCategory::all();
        $provisionsList = Provision::all();
        $provisionCategories = ProvisionCategory::all();

        return view('pdf.provisions', compact('provisions', 'displayAmount', 'displayClient', 'amountSum', 'netAmountSum', 'provisionsList', 'clientCategories', 'provisionCategories', 'provisionCategoryId', 'provisionId', 'clientCategoryId'));
    }
}
