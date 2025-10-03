<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use Illuminate\Http\Request;
use App\Models\ClientCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use App\Services\ProvisionComparisonService;

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
                'contacts',
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

        $clients->each(function ($client) {
            $clientOrder = $client->currentEngagement?->stage?->getLabel();
            $client->order = $clientOrder;
        });

        $clients = $clients->sortBy([
            ['category.name', 'asc'],
            ['order', 'asc'],
            ['name', 'asc'],
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

    public function clientProvisions()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $clients = Client::whereHas('provisionElements', function ($query) use ($edition) {
            $query->where('edition_id', $edition->id);
        })
            ->with([
                'category',
                'contacts',
                'currentEngagement',
                'provisionElements' => function ($query) use ($edition) {
                    $query->where('edition_id', $edition->id);
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
        ]);

        $view = View::make('pdf.client-provisions', ['clients' => $clients, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-client-provisions.pdf');

        return $pdf;
    }

    public function provisionsComparison(Request $request, ProvisionComparisonService $comparisonService)
    {
        $request->validate([
            'reference_edition_id'  => 'required|exists:editions,id',
            'comparison_edition_id' => 'required|exists:editions,id',
            'client_category_id'    => 'nullable|exists:client_categories,id',
        ]);

        $referenceEdition = Edition::find($request->input('reference_edition_id'));
        $comparisonEdition = Edition::find($request->input('comparison_edition_id'));
        $clientCategoryId = $request->input('client_category_id');
        $clientCategory = $clientCategoryId ? ClientCategory::find($clientCategoryId) : null;

        $comparisonData = $comparisonService->compareEditions($referenceEdition, $comparisonEdition, $clientCategoryId);

        $view = View::make('pdf.provisions-comparison', [
            'referenceEdition'  => $referenceEdition,
            'comparisonEdition' => $comparisonEdition,
            'comparisonData'    => $comparisonData,
            'clientCategory'    => $clientCategory,
        ]);

        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($referenceEdition->year.'-vs-'.$comparisonEdition->year)->slug().'-provisions-comparison.pdf');

        return $pdf;
    }
}
