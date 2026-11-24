<?php

namespace App\Filament\Actions\Support;

use Throwable;
use App\Models\Client;
use App\Models\Voucher;
use App\Helpers\AppHelper;
use Filament\Schemas\Components\Text;
use Filament\Forms\Components\Textarea;
use App\Notifications\ClientSendVouchers;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Envoi des vouchers et quotas Trail aux clients : éligibilité, aperçu avant envoi,
 * envoi unitaire ou groupé et compte rendu détaillé.
 */
class VoucherMailing
{
    public static function editionId(): int
    {
        return (int) (AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id'));
    }

    /**
     * Adresse e-mail à laquelle l'email serait réellement envoyé.
     */
    public static function recipient(Client $client): ?string
    {
        $route = $client->routeNotificationForMail(new ClientSendVouchers(collect()));
        $email = is_array($route) ? array_key_first($route) : $route;

        return filled($email) ? (string) $email : null;
    }

    /**
     * Clients de l'édition courante ayant au moins un voucher attribué ou un quota Trail.
     *
     * @return SupportCollection<int, Client>
     */
    public static function eligibleClients(?int $editionId = null): SupportCollection
    {
        $editionId ??= self::editionId();
        $trailProvisionIds = collect(setting('voucher_trail_provisions', []))
            ->flatten()
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        return Client::with(['currentProvisionElements'])
            ->where(function (Builder $query) use ($editionId, $trailProvisionIds) {
                $query->whereHas('vouchers', fn (Builder $q) => $q->where('edition_id', $editionId));

                if ($trailProvisionIds !== []) {
                    $query->orWhereHas('currentProvisionElements', fn (Builder $q) => $q->whereIn('provision_id', $trailProvisionIds));
                }
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (Client $client) => $client->vouchers()->where('edition_id', $editionId)->exists() || $client->trail_vouchers_quota > 0)
            ->keyBy('id');
    }

    /**
     * Évalue la situation d'un client pour l'envoi de ses vouchers.
     *
     * @return array{email: ?string, vouchers: SupportCollection, trail_quota: int, disabled: ?string, warning: ?string, checked: bool, description: string}
     */
    public static function assess(Client $client, ?int $editionId = null): array
    {
        $editionId ??= self::editionId();
        $vouchers = Voucher::where('client_id', $client->id)->where('edition_id', $editionId)->get();
        $cdnCount = $vouchers->count();
        $cdnQuota = $client->cdn_vouchers_quota;
        $missing = $client->missing_vouchers_count;
        $trailQuota = $client->trail_vouchers_quota;
        $email = self::recipient($client);

        $disabled = null;
        $warning = null;
        $info = [];

        if ($email === null) {
            $disabled = 'pas d\'e-mail';
        } elseif ($cdnCount === 0 && $trailQuota === 0) {
            $disabled = 'aucun voucher attribué ni quota Trail';
        } else {
            if ($cdnCount > 0) {
                $info[] = $cdnCount.' code(s) CDN';
            }
            if ($trailQuota > 0) {
                $info[] = $trailQuota.' place(s) Trail';
            }
            if ($missing > 0) {
                $warning = "quota incomplet ({$cdnCount}/{$cdnQuota}, manque {$missing})";
            }
        }

        $description = $disabled
            ? 'Ignoré : '.$disabled
            : '→ '.$email.($info ? ' · '.implode(' · ', $info) : '').($warning ? ' · ⚠ '.$warning : '');

        return [
            'email'       => $email,
            'vouchers'    => $vouchers,
            'trail_quota' => $trailQuota,
            'disabled'    => $disabled,
            'warning'     => $warning,
            'checked'     => $disabled === null && $warning === null,
            'description' => $description,
        ];
    }

    /**
     * Schéma de modale partagé pour l'envoi groupé (header ou bulk action).
     *
     * @param  SupportCollection<int, Client>  $clients
     * @return array<int, mixed>
     */
    public static function previewSchema(SupportCollection $clients, int $unassignedCount = 0): array
    {
        $editionId = self::editionId();
        $preview = ['options' => [], 'descriptions' => [], 'default' => [], 'disabled' => [], 'counts' => []];
        $counts = ['send' => 0, 'warning' => 0, 'ignored' => 0];

        foreach ($clients as $id => $client) {
            $assessment = self::assess($client, $editionId);
            $preview['options'][$id] = $client->name;
            $preview['descriptions'][$id] = $assessment['description'];

            if ($assessment['disabled']) {
                $preview['disabled'][] = $id;
                $counts['ignored']++;
            } elseif ($assessment['warning']) {
                $counts['warning']++;
            } else {
                $counts['send']++;
            }

            if ($assessment['checked']) {
                $preview['default'][] = $id;
            }
        }

        $view = BulkInvoicePreview::compact($preview, 15, 'ignorés');
        $list = $view['preview'];

        $summaryParts = [
            $clients->count().' client(s)',
            $counts['send'].' prêt(s)',
        ];
        if ($counts['warning'] > 0) {
            $summaryParts[] = $counts['warning'].' quota incomplet (décoché(s))';
        }
        if ($counts['ignored'] > 0) {
            $summaryParts[] = $counts['ignored'].' ignoré(s)';
        }
        if ($unassignedCount > 0) {
            $summaryParts[] = $unassignedCount.' voucher(s) sans client';
        }

        $components = [
            Text::make(implode(' · ', $summaryParts))->weight('bold'),
        ];

        if ($list['options'] !== []) {
            $components[] = CheckboxList::make('client_ids')
                ->label('Clients destinataires')
                ->options($list['options'])
                ->descriptions($list['descriptions'])
                ->default($list['default'])
                ->disableOptionWhen(fn (string $value) => in_array((int) $value, $list['disabled'], true))
                ->bulkToggleable()
                ->searchable();
        }

        if ($view['summary']) {
            $components[] = Text::make($view['summary'])->color('gray');
        }

        $components[] = Textarea::make('custom_message')
            ->label('Message personnalisé commun (optionnel)')
            ->placeholder('Ex : Voici vos codes vouchers pour la Course de Noël...');

        return $components;
    }

    /**
     * Envoie les vouchers à un client donné.
     */
    public static function send(Client $client, ?string $customMessage = null, ?int $editionId = null): void
    {
        $editionId ??= self::editionId();
        $vouchers = Voucher::where('client_id', $client->id)->where('edition_id', $editionId)->get();

        $client->notify(new ClientSendVouchers(
            $vouchers,
            filled($customMessage) ? $customMessage : null,
            $client->trail_vouchers_quota
        ));
    }

    /**
     * Exécute l'envoi groupé avec rapport persistant.
     *
     * @param  SupportCollection<int, Client>  $clients
     */
    public static function sendBulk(SupportCollection $clients, array $data): void
    {
        $editionId = self::editionId();
        $report = new BulkMailReport('Envoi des vouchers');
        $selected = collect($data['client_ids'] ?? [])->map(fn ($id) => (int) $id);
        $customMessage = $data['custom_message'] ?? null;

        foreach ($clients as $id => $client) {
            if (! $selected->contains($id)) {
                $report->skipped($client->name, 'non coché');

                continue;
            }

            $assessment = self::assess($client, $editionId);
            if ($assessment['disabled']) {
                $report->skipped($client->name, $assessment['disabled']);

                continue;
            }

            try {
                self::send($client, $customMessage, $editionId);
                $report->sent($client->name, $assessment['email']);
            } catch (Throwable $e) {
                report($e);
                $report->failed($client->name, $e->getMessage());
            }
        }

        $report->send();
    }
}
