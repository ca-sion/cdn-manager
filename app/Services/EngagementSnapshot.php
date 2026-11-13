<?php

namespace App\Services;

use App\Helpers\AppHelper;
use App\Models\ClientEngagement;

/**
 * Mémorise et restaure le suivi (étape + statut) des clients autour d'une action en masse,
 * pour que « Annuler ces factures » remette le suivi dans l'état d'avant.
 *
 * Un snapshot : ['existed' => bool, 'stage' => ?string, 'status' => ?string]
 */
class EngagementSnapshot
{
    protected static function editionId(): int
    {
        return (int) (AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id'));
    }

    /**
     * @param  iterable<int|string>  $clientIds
     * @return array<int, array{existed: bool, stage: ?string, status: ?string}>
     */
    public static function capture(iterable $clientIds): array
    {
        $ids = collect($clientIds)->map(fn ($id) => (int) $id)->unique()->values();

        $engagements = ClientEngagement::withoutGlobalScopes()
            ->where('edition_id', self::editionId())
            ->whereIn('client_id', $ids)
            ->get()
            ->keyBy('client_id');

        return $ids->mapWithKeys(fn (int $id) => [$id => [
            'existed' => $engagements->has($id),
            'stage'   => $engagements[$id]?->stage?->value,
            'status'  => $engagements[$id]?->status?->value,
        ]])->all();
    }

    /**
     * Clients dont le suivi est toujours exactement celui posé par l'action (« after ») :
     * si quelqu'un l'a modifié entre-temps, on n'y touche plus.
     *
     * @param  array<int, array{before: array, after: array}>  $engagements
     * @return array<int, int>
     */
    public static function untouchedSince(array $engagements): array
    {
        $current = self::capture(array_keys($engagements));

        return collect($engagements)
            ->filter(fn (array $pair, $clientId) => ($current[(int) $clientId]['stage'] ?? null) === ($pair['after']['stage'] ?? null)
                && ($current[(int) $clientId]['status'] ?? null) === ($pair['after']['status'] ?? null))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Remet le suivi des clients dans l'état « before ».
     * Un suivi qui n'existait pas avant est supprimé s'il ne contient rien d'autre (dates, note, responsable).
     *
     * @param  array<int, array{before: array, after: array}>  $engagements
     * @param  array<int, int>  $clientIds
     */
    public static function restore(array $engagements, array $clientIds): void
    {
        foreach ($clientIds as $clientId) {
            $before = $engagements[$clientId]['before'] ?? null;
            $engagement = ClientEngagement::withoutGlobalScopes()
                ->where('edition_id', self::editionId())
                ->where('client_id', $clientId)
                ->first();

            if (! $before || ! $engagement) {
                continue;
            }

            if (! $before['existed']) {
                $hasOtherData = $engagement->sent_at || $engagement->viewed_at || $engagement->relaunched_at
                    || $engagement->note || $engagement->responsible || $engagement->responsible_contact_id;

                $hasOtherData
                    ? $engagement->forceFill(['stage' => null, 'status' => null])->save()
                    : $engagement->delete();

                continue;
            }

            $engagement->forceFill(['stage' => $before['stage'], 'status' => $before['status']])->save();
        }
    }
}
