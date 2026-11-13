<?php

namespace App\Filament\Actions\Support;

use App\Models\Client;
use Illuminate\Support\Number;
use App\Services\InvoiceService;
use Illuminate\Support\Collection;
use App\Services\RunRegistrationService;

/**
 * Construit les lignes d'aperçu (une par client) des générations de factures en masse.
 * Chaque méthode retourne : options (id => nom), descriptions (id => texte), default (ids cochés),
 * disabled (ids sans rien à facturer) et counts.
 */
class BulkInvoicePreview
{
    /**
     * Factures de PRESTATIONS.
     *
     * @param  array<int, int>  $clientIds
     * @return array{options: array, descriptions: array, default: array, disabled: array, counts: array}
     */
    public static function provisions(array $clientIds): array
    {
        $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');
        $result = ['options' => [], 'descriptions' => [], 'default' => [], 'disabled' => [], 'counts' => ['new' => 0, 'draft' => 0, 'nothing' => 0]];

        foreach ($clientIds as $clientId) {
            $preview = InvoiceService::previewInvoiceByClient($clientId);
            $result['counts'][$preview['action']]++;
            $result['options'][$clientId] = $names[$clientId] ?? 'Client #'.$clientId;
            $amount = Number::currency($preview['amount'], 'CHF', 'fr_CH');

            if ($preview['action'] === 'nothing') {
                $result['descriptions'][$clientId] = 'Rien à facturer ('.$preview['reason'].')';
                $result['disabled'][] = $clientId;

                continue;
            }

            $result['descriptions'][$clientId] = ($preview['action'] === 'draft'
                ? "Complète le brouillon {$preview['draft']->number}"
                : 'Nouvelle facture')." · {$preview['count']} prestation(s) · {$amount}";
            $result['default'][] = $clientId;
        }

        return $result;
    }

    /**
     * Factures d'INSCRIPTIONS de courses (par client).
     *
     * @param  array<int, int>  $clientIds
     * @param  Collection|null  $selected  inscriptions cochées par l'utilisateur (pour dire « dont N dans votre sélection »)
     * @return array{options: array, descriptions: array, default: array, disabled: array, counts: array}
     */
    public static function registrations(array $clientIds, ?Collection $selected = null): array
    {
        $service = app(RunRegistrationService::class);
        $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');
        $result = ['options' => [], 'descriptions' => [], 'default' => [], 'disabled' => [], 'counts' => ['todo' => 0, 'nothing' => 0]];

        foreach ($clientIds as $clientId) {
            $plan = $service->plan($clientId);
            $result['options'][$clientId] = $names[$clientId] ?? 'Client #'.$clientId;

            if ($plan['elements']->isEmpty()) {
                $result['counts']['nothing']++;
                $result['descriptions'][$clientId] = $plan['registrations']->isEmpty() && $plan['elite_excluded'] > 0
                    ? 'Rien à facturer (inscriptions Élite uniquement)'
                    : 'Rien à facturer ('.($plan['total_elements'] > 0 ? 'toutes les inscriptions sont déjà facturées' : 'aucune inscription').')';
                $result['disabled'][] = $clientId;

                continue;
            }

            $result['counts']['todo']++;
            $result['default'][] = $clientId;
            $amount = Number::currency($plan['amount'], 'CHF', 'fr_CH');

            $text = ($plan['draft'] ? "Recalcule le brouillon {$plan['draft']->number}" : 'Nouvelle facture')
                ." · {$plan['elements']->count()} inscription(s)";
            if ($selected) {
                $inSelection = $plan['elements']->whereIn('run_registration_id', $selected->where('client_id', $clientId)->pluck('id'))->count();
                $text .= " ({$inSelection} dans votre sélection)";
            }
            $text .= " · {$amount}";
            if ($plan['quota_ids'] !== []) {
                $text .= ' · '.count($plan['quota_ids']).' marquée(s) « incluse(s) » par le quota';
            }
            if ($plan['warnings'] !== []) {
                $text .= ' ⚠ '.implode(' ; ', $plan['warnings']);
            }
            $result['descriptions'][$clientId] = $text;
        }

        return $result;
    }
}
