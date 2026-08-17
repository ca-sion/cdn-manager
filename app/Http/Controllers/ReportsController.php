<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Edition;
use App\Models\Invoice;
use Illuminate\Http\Request;
use App\Models\ClientCategory;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\InvoiceStatusEnum;
use App\Models\ProvisionElement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\RunRegistrationElement;
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
        ])->flatten()->filter()->values()->all();

        $donationProvisionId = setting('advertiser_form_donation_provision');
        $clientCategoryIds = collect(setting('reports_advertisers_categories', []))->flatten()->filter()->values()->all();

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
            'client_category_ids'   => 'nullable|array',
            'client_category_ids.*' => 'exists:client_categories,id',
            'client_category_id'    => 'nullable|exists:client_categories,id',
        ]);

        $referenceEdition = Edition::find($request->input('reference_edition_id'));
        $comparisonEdition = Edition::find($request->input('comparison_edition_id'));

        $clientCategoryIds = (array) $request->input('client_category_ids', []);
        if (empty($clientCategoryIds) && $request->filled('client_category_id')) {
            $clientCategoryIds = [(int) $request->input('client_category_id')];
        }

        $clientCategoryIds = array_values(array_filter($clientCategoryIds));
        $clientCategories = ! empty($clientCategoryIds)
            ? ClientCategory::whereIn('id', $clientCategoryIds)->orderBy('name')->get()
            : collect();

        $comparisonData = $comparisonService->compareEditions($referenceEdition, $comparisonEdition, $clientCategoryIds);

        $view = View::make('pdf.provisions-comparison', [
            'referenceEdition'  => $referenceEdition,
            'comparisonEdition' => $comparisonEdition,
            'comparisonData'    => $comparisonData,
            'clientCategories'  => $clientCategories,
            'clientCategory'    => $clientCategories->first(),
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

        $journalProvisionIds = collect(setting('reports_advertisers_journal_provisions', []))->flatten()->filter()->values()->all();

        abort_if(empty($journalProvisionIds), '401');

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
            ['vip_name', 'asc'],
        ]);

        if (request()->input('export')) {
            $provisions = $provisions->sortBy([
                ['vip_name', 'asc'],
            ])->loadMissing('recipient');
            $exportCollection = $provisions->map(function ($pe) {
                return [
                    'name'                      => $pe->vip_name,
                    'first_name'                => $pe->recipient?->first_name ?? $pe->client?->contacts()?->orderBy('order_column')->first()?->name,
                    'category'                  => $pe->recipient?->category?->name,
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

    public function banners()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $bannerProvisionIds = collect(setting('reports_banners_provisions', []))->flatten()->filter()->values()->all();

        abort_if(empty($bannerProvisionIds), '401');

        $provisions = ProvisionElement::with(['recipient', 'recipient.category', 'provision'])
            ->where('edition_id', $edition->id)
            ->whereIn('provision_id', $bannerProvisionIds)
            ->get();

        $provisions->each(function ($provision) {
            $provisionOrder = $provision->status?->getLabel();
            $provision->order = $provisionOrder;
        });

        $provisions = $provisions->sortBy([
            ['order', 'asc'],
            ['recipient.locality', 'asc'],
            ['recipient.name', 'asc'],
        ]);

        if (request()->input('export')) {
            $exportCollection = $this->flattenRelations($provisions, [
                'provision',
                'recipient.category',
            ]);

            return (new FastExcel($exportCollection))->download($edition?->year.'-banners-provisions.xlsx');
        }

        $view = View::make('pdf.banners', ['provisions' => $provisions, 'edition' => $edition]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-banners.pdf');

        return $pdf;
    }

    public function screens()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $screenProvisionIds = collect(setting('reports_screens_provisions', []))->flatten()->filter()->values()->all();

        abort_if(empty($screenProvisionIds), '401');

        $provisions = ProvisionElement::with(['recipient', 'recipient.category', 'provision'])
            ->where('edition_id', $edition->id)
            ->whereIn('provision_id', $screenProvisionIds)
            ->get();

        $provisions->each(function ($provision) {
            $provisionOrder = $provision->status?->getLabel();
            $provision->order = $provisionOrder;
        });

        $provisions = $provisions->sortBy([
            ['order', 'asc'],
            ['provision.name', 'asc'],
            ['recipient.name', 'asc'],
        ]);

        if (request()->input('export')) {
            $exportCollection = $this->flattenRelations($provisions, [
                'provision',
                'recipient.category',
            ]);

            return (new FastExcel($exportCollection))->download($edition?->year.'-screens-provisions.xlsx');
        }

        $view = View::make('pdf.screens', ['provisions' => $provisions, 'edition' => $edition]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-screens.pdf');

        return $pdf;
    }

    public function financialReport()
    {
        $editionYear = request()->input('edition');

        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        // 1. Unpaid Invoices (Existing logic)
        $unpaidStatuses = [
            InvoiceStatusEnum::Sent,
            InvoiceStatusEnum::Relaunched,
            InvoiceStatusEnum::ActionRequired,
            InvoiceStatusEnum::Overdue,
            InvoiceStatusEnum::SentByPost,
            InvoiceStatusEnum::ToModify,
            InvoiceStatusEnum::ToRelaunch,
        ];

        $unpaidInvoices = Invoice::where('edition_id', $edition->id)
            ->whereIn('status', $unpaidStatuses)
            ->with(['client.category'])
            ->get();

        $groupedUnpaidInvoices = $unpaidInvoices->groupBy(function ($invoice) {
            return $invoice->client->category->name ?? 'Sans catégorie';
        })->sortKeys();

        $unpaidTotal = $unpaidInvoices->sum(function ($invoice) {
            return $invoice->total;
        });

        // 2. All Valid Invoices for Financial Reporting
        // Exclude Draft and Cancelled
        $validStatuses = collect(InvoiceStatusEnum::cases())
            ->reject(fn ($status) => in_array($status, [InvoiceStatusEnum::Draft, InvoiceStatusEnum::Cancelled]))
            ->pluck('value')
            ->toArray();

        $allInvoices = Invoice::where('edition_id', $edition->id)
            ->whereIn('status', $validStatuses)
            ->with(['client.category'])
            ->get();

        // 3. Invoiced by Client Category
        $invoicedByCategory = $allInvoices->groupBy(function ($invoice) {
            return $invoice->client->category->name ?? 'Sans catégorie';
        })->map(function ($invoices) {
            return $invoices->sum(fn ($i) => $i->total);
        })->sortKeys();

        $totalInvoiced = $allInvoices->sum(fn ($i) => $i->total);

        // 4. Invoiced by Product (Provision Element)
        // Parse 'items' from invoice positions
        $invoicedByProduct = collect();

        $allInvoices->each(function ($invoice) use (&$invoicedByProduct) {
            foreach ($invoice->items as $item) {
                // $item is an object with { name, cost, quantity, price: { amount ... } }
                // Aggregate by name
                $name = $item->name;
                $amount = $item->price->amount ?? 0;
                $quantity = $item->quantity ?? 0;

                if (! $invoicedByProduct->has($name)) {
                    $invoicedByProduct->put($name, [
                        'name'     => $name,
                        'quantity' => 0,
                        'total'    => 0,
                    ]);
                }

                $current = $invoicedByProduct->get($name);
                $current['quantity'] += $quantity;
                $current['total'] += $amount;
                $invoicedByProduct->put($name, $current);
            }
        });

        $invoicedByProduct = $invoicedByProduct->sortBy('name');

        $view = View::make('pdf.financial-report', [
            'groupedUnpaidInvoices' => $groupedUnpaidInvoices,
            'unpaidTotal'           => $unpaidTotal,
            'invoicedByCategory'    => $invoicedByCategory,
            'totalInvoiced'         => $totalInvoiced,
            'invoicedByProduct'     => $invoicedByProduct,
            'edition'               => $edition,
        ]);

        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-rapport-financier.pdf');

        return $pdf;
    }

    public function invoices()
    {
        $editionYear = request()->input('edition');
        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $invoices = Invoice::where('edition_id', $edition->id)
            ->with(['client'])
            ->orderBy('number', 'asc')
            ->get();

        $grandTotal = $invoices->sum('total');

        if (request()->input('export')) {
            $exportData = $invoices->map(fn ($inv) => [
                'N° Facture'    => '#'.$inv->number,
                'Client'        => $inv->client?->name ?? $inv->invoicing_company_name,
                'Date'          => $inv->created_at?->format('d.m.Y'),
                'Échéance'      => $inv->due_at?->format('d.m.Y'),
                'Statut'        => is_object($inv->status) && method_exists($inv->status, 'getLabel') ? $inv->status->getLabel() : (string) ($inv->status?->value ?? $inv->status),
                'Montant Total' => $inv->total,
            ]);

            return (new FastExcel($exportData))->download($edition?->year.'-invoices.xlsx');
        }

        $view = View::make('pdf.invoices', ['invoices' => $invoices, 'edition' => $edition, 'grandTotal' => $grandTotal]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-invoices.pdf');
    }

    public function elites()
    {
        $editionYear = request()->input('edition');
        $edition = Edition::where('year', $editionYear)->first() ?? Edition::find(setting('edition_id', config('cdn.default_edition_id')));

        $elements = RunRegistrationElement::whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', 'elite'))
            ->with(['run', 'runRegistration'])
            ->get();

        $totalStartBonus = $elements->sum('bonus_start_amount');
        $totalArrivalBonus = $elements->sum('bonus_arrival_amount');
        $totalRankingBonus = $elements->sum('bonus_ranking_amount');

        if (request()->input('export')) {
            $exportData = $elements->map(fn ($el) => [
                'Nom'               => $el->last_name,
                'Prénom'            => $el->first_name,
                'Date Naissance'    => $el->birthdate?->format('d.m.Y'),
                'Genre'             => is_object($el->gender) ? $el->gender->value : $el->gender,
                'Nationalité'       => $el->nationality,
                'Email'             => $el->email,
                'Course'            => $el->run?->name ?? $el->run_name,
                'Bloc'              => $el->bloc,
                'IBAN'              => $el->iban ?: $el->runRegistration?->payment_iban,
                'Prime départ'      => $el->bonus_start_amount,
                'Prime arrivée'     => $el->bonus_arrival_amount,
                'Prime classement'  => $el->bonus_ranking_amount,
                'Hébergement'       => $el->has_accommodation ? 'Oui' : 'Non',
                'Nuitée Vendredi'   => $el->accommodation_friday ? 'Oui' : 'Non',
                'Nuitée Samedi'     => $el->accommodation_saturday ? 'Oui' : 'Non',
                'Précisions héb.'   => $el->accommodation_precision,
                'Defraiement frais' => $el->has_expense_reimbursement ? 'Oui' : 'Non',
            ]);

            return (new FastExcel($exportData))->download($edition?->year.'-elites.xlsx');
        }

        $view = View::make('pdf.elites', ['elements' => $elements, 'edition' => $edition, 'totalStartBonus' => $totalStartBonus, 'totalArrivalBonus' => $totalArrivalBonus, 'totalRankingBonus' => $totalRankingBonus]);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        return Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->setOption(['defaultFont' => 'sans-serif', 'enable_php' => true])
            ->stream(str($edition->year)->slug().'-coureurs-elite.pdf');
    }

    /**
     * Aplatit les attributs de relations spécifiées sur chaque élément d'une collection.
     * Gère les relations BelongsTo/HasOne et agrège les relations HasMany.
     *
     * @param  Collection  $collection  La collection Eloquent à transformer.
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
