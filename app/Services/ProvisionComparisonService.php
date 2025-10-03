<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Edition;

class ProvisionComparisonService
{
    public function compareEditions(Edition $referenceEdition, Edition $comparisonEdition): array
    {
        // 1. Get all client IDs that have provision elements in either edition
        $clientIdsInReference = Client::whereHas('provisionElements', fn ($q) => $q->where('edition_id', $referenceEdition->id))
            ->pluck('id');

        $clientIdsInComparison = Client::whereHas('provisionElements', fn ($q) => $q->where('edition_id', $comparisonEdition->id))
            ->pluck('id');

        $allClientIds = $clientIdsInReference->merge($clientIdsInComparison)->unique();

        $clients = Client::with([
            'provisionElements' => function ($query) use ($referenceEdition, $comparisonEdition) {
                $query->whereIn('edition_id', [$referenceEdition->id, $comparisonEdition->id]);
            },
            'provisionElements.provision',
        ])->findMany($allClientIds)->sortBy('name');

        $comparisonData = [
            'new'       => collect(),
            'lost'      => collect(),
            'modified'  => collect(),
            'unchanged' => collect(),
            'totals'    => [
                'new'              => 0,
                'lost'             => 0,
                'modified_gain'    => 0,
                'modified_loss'    => 0,
                'reference_total'  => 0,
                'comparison_total' => 0,
            ],
        ];

        foreach ($clients as $client) {
            $referenceProvisions = $client->provisionElements->where('edition_id', $referenceEdition->id);
            $comparisonProvisions = $client->provisionElements->where('edition_id', $comparisonEdition->id);

            $hasReferenceProvisions = $referenceProvisions->isNotEmpty();
            $hasComparisonProvisions = $comparisonProvisions->isNotEmpty();

            $referenceTotal = $referenceProvisions->sum(fn ($el) => $el->price->amount);
            $comparisonTotal = $comparisonProvisions->sum(fn ($el) => $el->price->amount);

            $comparisonData['totals']['reference_total'] += $referenceTotal;
            $comparisonData['totals']['comparison_total'] += $comparisonTotal;

            if ($hasReferenceProvisions && ! $hasComparisonProvisions) {
                $client->diff_details = ['provisions' => $referenceProvisions, 'total' => $referenceTotal];
                $comparisonData['new']->push($client);
                $comparisonData['totals']['new'] += $referenceTotal;
            } elseif (! $hasReferenceProvisions && $hasComparisonProvisions) {
                $client->diff_details = ['provisions' => $comparisonProvisions, 'total' => $comparisonTotal];
                $comparisonData['lost']->push($client);
                $comparisonData['totals']['lost'] += $comparisonTotal;
            } elseif ($hasReferenceProvisions && $hasComparisonProvisions) {
                $referenceSignature = $referenceProvisions->map(fn ($el) => $el->provision_id.':'.$el->quantity)->sort()->implode('|');
                $comparisonSignature = $comparisonProvisions->map(fn ($el) => $el->provision_id.':'.$el->quantity)->sort()->implode('|');

                if ($referenceSignature === $comparisonSignature && $referenceTotal === $comparisonTotal) {
                    $comparisonData['unchanged']->push($client);
                } else {
                    $diff = $referenceTotal - $comparisonTotal;
                    $client->diff_details = [
                        'reference_provisions'  => $referenceProvisions,
                        'comparison_provisions' => $comparisonProvisions,
                        'reference_total'       => $referenceTotal,
                        'comparison_total'      => $comparisonTotal,
                        'diff'                  => $diff,
                    ];
                    $comparisonData['modified']->push($client);

                    if ($diff > 0) {
                        $comparisonData['totals']['modified_gain'] += $diff;
                    } else {
                        $comparisonData['totals']['modified_loss'] += $diff;
                    }
                }
            }
        }

        return $comparisonData;
    }
}
