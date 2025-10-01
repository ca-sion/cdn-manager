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

class ReportsController extends Controller
{
    public function advertisers()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $provisionCategoryIds = collect([
            setting('advertiser_form_journal_category'),
            setting('advertiser_form_banner_category'),
            setting('advertiser_form_screen_category'),
            setting('advertiser_form_pack_category'),
        ])->filter()->all();

        $donationProvisionId = setting('advertiser_form_donation_provision');
        $clientCategoryIds = setting('reports_advertisers_categories', []);

        $clients = Client::whereIn('category_id', $clientCategoryIds)
            ->with([
                'category',
                'currentEngagement',
                'provisionElements' => function ($query) use ($provisionCategoryIds, $donationProvisionId, $edition) {
                    $query->where('edition_id', $edition->id)
                        ->where(function ($q) use ($provisionCategoryIds, $donationProvisionId) {
                            $q->whereHas('provision', function ($subQ) use ($provisionCategoryIds) {
                                $subQ->whereIn('category_id', $provisionCategoryIds);
                            })->orWhere('provision_id', $donationProvisionId);
                        });
                },
            ])
            ->get();

        $grandTotal = 0;
        $clients->each(function ($client) use (&$grandTotal) {
            $clientTotal = $client->provisionElements->sum(function ($element) {
                return $element->price->amount ?? 0;
            });
            $client->advertiser_total = $clientTotal;
            $grandTotal += $clientTotal;
        });

        $clients = $clients->sortBy([
            ['category.name', 'asc'],
            ['name', 'asc'],
            // ['currentEngagement.stage', 'asc'],
            // fn ($client) => $client->currentEngagement?->stage?->getLabel(),
        ]);

        $view = View::make('pdf.advertisers', ['clients' => $clients, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-advertisers.pdf');

        return $pdf;
    }

    public function donors()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $donationProvisionId = setting('advertiser_form_donation_provision');

        $contacts = Contact::whereHas('provisionElements', function ($query) use ($donationProvisionId, $edition) {
            $query->where('edition_id', $edition->id)
                ->where('provision_id', $donationProvisionId);
        })
            ->with([
                'category',
                'provisionElements' => function ($query) use ($donationProvisionId, $edition) {
                    $query->where('edition_id', $edition->id)
                        ->where('provision_id', $donationProvisionId);
                },
            ])
            ->get();

        $grandTotal = 0;
        $contacts->each(function ($contact) use (&$grandTotal) {
            $contactTotal = $contact->provisionElements->sum(function ($element) {
                return $element->price->amount ?? 0;
            });
            $contact->donation_total = $contactTotal;
            $grandTotal += $contactTotal;
        });

        $contacts = $contacts->sortBy([
            ['category.name', 'asc'],
            ['donation_total', 'desc'],
            ['name', 'asc'],
        ]);

        $view = View::make('pdf.donors', ['contacts' => $contacts, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-donors.pdf');

        return $pdf;
    }
}
