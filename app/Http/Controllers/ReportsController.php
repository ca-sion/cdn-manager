<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use Illuminate\Http\Request;
use App\Models\ClientCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ProvisionElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Rap2hpoutre\FastExcel\FastExcel;
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

        $clients->each(function ($client) use ($edition) {
            $client->had_previous_provisions = $client->provisionElements()
                ->whereHas('edition', function ($query) use ($edition) {
                    $query->where('year', '<', $edition->year);
                })
                ->exists();
        });

        $clients->each(function ($client) {
            $clientOrder = $client->currentEngagement?->stage?->getLabel();
            $client->order = $clientOrder;
        });

        $clients = $clients->sortBy([
            ['category.name', 'asc'],
            ['order', 'asc'],
            ['name', 'asc'],
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

        if (request()->input('export')) {
            $exportCollection = $this->flattenRelations($contacts, [
                'category',
            ]);

            return (new FastExcel($exportCollection))->download($edition?->year.'-donors.xlsx');
        }

        $view = View::make('pdf.donors', ['contacts' => $contacts, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-donors.pdf');

        return $pdf;
    }

    public function interclassDonors()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $interclassProvisionId = setting('reports_interclass_donor_provision');

        $clients = Client::whereHas('provisionElements', function ($query) use ($interclassProvisionId, $edition) {
            $query->where('edition_id', $edition->id)
                ->where('provision_id', $interclassProvisionId);
        })
            ->with([
                'category',
                'provisionElements' => function ($query) use ($interclassProvisionId, $edition) {
                    $query->where('edition_id', $edition->id)
                        ->where('provision_id', $interclassProvisionId);
                },
            ])
            ->get();

        $grandTotal = 0;
        $clients->each(function ($client) use (&$grandTotal) {
            $clientTotal = $client->provisionElements->sum(function ($element) {
                return $element->numeric_indicator ?? 0;
            });
            $client->donor_total = $clientTotal;
            $grandTotal += $clientTotal;
        });

        $clients = $clients->sortBy([
            ['name', 'asc'],
        ]);

        if (request()->input('export')) {
            $exportCollection = $this->flattenRelations($clients, [
                'category',
            ]);

            return (new FastExcel($exportCollection))->download($edition?->year.'-interclass-donors.xlsx');
        }

        $view = View::make('pdf.interclass-donors', ['clients' => $clients, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-interclass_donors.pdf');

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

    public function journalProvisions()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $journalProvisionIds = setting('reports_advertisers_journal_provisions');

        abort_if(! $journalProvisionIds, '401');

        $provisions = ProvisionElement::with(['recipient.category', 'provision'])
            ->where('edition_id', $edition->id)
            ->whereIn('provision_id', $journalProvisionIds)
            ->get();

        $provisions = $provisions->sortBy([
            ['provision.name', 'asc'],
            ['recipient.name', 'asc'],
        ]);

        if (request()->input('export')) {
            $exportCollection = $this->flattenRelations($provisions, [
                'provision',
                'recipient.category',
            ]);

            return (new FastExcel($exportCollection))->download($edition?->year.'-journal-provisions.xlsx');
        }

        $view = View::make('pdf.journal-provisions', ['provisions' => $provisions, 'edition' => $edition]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-journal-provisions.pdf');

        return $pdf;
    }

    public function vip()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $vipProvisionId = setting('vip_provision');

        abort_if(! $vipProvisionId, '401');

        $provisions = ProvisionElement::with(['recipient', 'recipient.category'])
            ->where('edition_id', $edition->id)
            ->where('provision_id', $vipProvisionId)
            ->get();

        $provisions = $provisions->sortBy([
            ['recipient.category.name', 'asc'],
            ['recipient.name', 'asc'],
        ]);

        if (request()->input('export')) {
            $provisions = $provisions->sortBy([
                ['vip_name', 'asc'],
            ])->loadMissing('recipient');
            $exportCollection = $provisions->map(function ($pe) {
                return [
                    'name'                      => $pe->vip_name,
                    'first_name'                => $pe->recipient?->first_name ? str($pe->recipient?->first_name)->limit(24) : str($pe->client?->contacts()?->orderBy('order_column')->first()?->name)->limit(24),
                    'role'                      => $pe->recipient?->role,
                    'company'                   => $pe->recipient?->company,
                    'email'                     => $pe->recipient?->vipContactEmail ?? $pe->recipient?->email,
                    'address'                   => $pe->recipient?->address,
                    'postal_code'               => $pe->recipient?->postal_code,
                    'locality'                  => $pe->recipient?->locality,
                    'vip_category'              => $pe->vip_category,
                    'vip_invitation_number'     => $pe->vip_invitation_number,
                    'vip_response_status'       => $pe->vip_response_status,
                    'vip_guests'                => collect($pe->vip_guests)->implode(', '),
                    'note'                      => $pe->note,
                    'vip_response_status_count' => $pe->vip_response_status == true ? collect($pe->vip_guests)->count() + 1 : null,
                ];
            });

            return (new FastExcel($exportCollection))->download($edition?->year.'-vip.xlsx');
        }

        $view = View::make('pdf.vip', ['provisions' => $provisions, 'edition' => $edition]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-vip.pdf');

        return $pdf;
    }

    /**
     * Aplatit les attributs de relations spécifiées sur chaque élément d'une collection.
     * Gère les relations BelongsTo/HasOne et agrège les relations HasMany.
     *
     * @param  \Illuminate\Support\Collection  $collection  La collection Eloquent à transformer.
     * @param  array  $relations  Les relations à aplatir (ex: ['provision', 'items']).
     */
    protected function flattenRelations(Collection $collection, array $relations): Collection
    {
        return $collection->map(function ($model) use ($relations) {
            $data = $model->toArray();

            foreach ($relations as $relationName) {
                $segments = explode('.', $relationName);
                $currentObject = $model;

                // 1. Trouver l'objet de relation finale
                foreach ($segments as $segment) {
                    if (isset($currentObject->{$segment})) {
                        $currentObject = $currentObject->{$segment};
                    } else {
                        $currentObject = null;
                        break;
                    }
                }

                if ($currentObject) {
                    // 2. Vérification CLÉ : Ignorer si c'est une collection (HasMany)
                    if ($currentObject instanceof Collection) {
                        // Ignorer les collections (relations HasMany).
                        // Pour FastExcel, une ligne = un enregistrement, pas une liste d'enregistrements.
                        continue;
                    }

                    // 3. Traitement des relations One-to-One (Modèle unique)
                    $prefix = str_replace('.', '_', $relationName);
                    $relationData = $currentObject->toArray();

                    foreach ($relationData as $key => $value) {
                        // Ajout des attributs préfixés
                        $data[$prefix.'_'.$key] = $value;
                    }
                }

                // Suppression de l'objet de relation complet du tableau final
                if (count($segments) === 1) {
                    unset($data[$relationName]);
                }
            }

            return $data;
        });
    }
}
