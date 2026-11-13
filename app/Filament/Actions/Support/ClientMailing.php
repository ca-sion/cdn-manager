<?php

namespace App\Filament\Actions\Support;

use App\Models\Client;
use App\Helpers\AppHelper;
use Illuminate\Support\Carbon;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use App\Notifications\ClientAdvertiserFormLink;
use App\Notifications\ClientAdvertiserFormRelaunch;
use App\Notifications\ClientInterclassDonorRequest;
use App\Notifications\ClientInterclassDonorRequestRelaunch;

/**
 * Les 4 emails envoyés aux clients (formulaire annonceur, relance annonceur, demande aux donateurs interclasses,
 * relance donateurs) : définitions, règles d'éligibilité et envoi, partagés par toutes les actions.
 *
 * Règles :
 * - un client sans e-mail ne reçoit rien
 * - premier envoi : si le client a déjà été contacté pour cette édition (date d'envoi partagée par les deux
 *   premiers envois), il est signalé et décoché par défaut
 * - relance : seulement un client déjà contacté et toujours à l'étape « Envoyé/Contacté » (sans réponse)
 */
class ClientMailing
{
    /**
     * @var array<string, array{label: string, short: string, relaunch: bool, notification: class-string, icon: string}>
     */
    public const DEFINITIONS = [
        'send_advertiser_form' => [
            'label'        => 'Envoyer le formulaire annonceur', 'short' => 'Formulaire annonceur', 'relaunch' => false,
            'notification' => ClientAdvertiserFormLink::class, 'icon' => 'heroicon-o-envelope',
        ],
        'relaunch_advertiser_form' => [
            'label'        => 'Relancer annonceur avec formulaire', 'short' => 'Relance annonceur', 'relaunch' => true,
            'notification' => ClientAdvertiserFormRelaunch::class, 'icon' => 'heroicon-o-arrow-path',
        ],
        'send_interclass_donors_email' => [
            'label'        => 'Envoyer la demande aux donateurs interclasses', 'short' => 'Demande donateurs interclasses', 'relaunch' => false,
            'notification' => ClientInterclassDonorRequest::class, 'icon' => 'heroicon-o-envelope',
        ],
        'relaunch_interclass_donors_email' => [
            'label'        => 'Relancer les donateurs interclasses', 'short' => 'Relance donateurs interclasses', 'relaunch' => true,
            'notification' => ClientInterclassDonorRequestRelaunch::class, 'icon' => 'heroicon-o-arrow-path',
        ],
    ];

    /** @return array{label: string, short: string, relaunch: bool, notification: class-string, icon: string} */
    public static function definition(string $key): array
    {
        return self::DEFINITIONS[$key] ?? throw new \InvalidArgumentException("Envoi inconnu : {$key}");
    }

    protected static function engagement(Client $client)
    {
        return $client->currentEngagement;
    }

    protected static function formatDate(?Carbon $date): ?string
    {
        return $date?->copy()->timezone('Europe/Zurich')->format('d.m.Y H:i');
    }

    /**
     * Adresse e-mail à laquelle l'email serait réellement envoyé (même règle que l'envoi).
     */
    public static function recipient(string $key, Client $client): ?string
    {
        $class = self::definition($key)['notification'];
        $route = $client->routeNotificationForMail(new $class($client, []));
        $email = is_array($route) ? array_key_first($route) : $route;

        return filled($email) ? (string) $email : null;
    }

    /**
     * Situation d'un client pour un envoi.
     *
     * @return array{email: ?string, disabled: ?string, warning: ?string, info: ?string, checked: bool, description: string}
     */
    public static function assess(string $key, Client $client): array
    {
        $definition = self::definition($key);
        $engagement = self::engagement($client);
        $email = self::recipient($key, $client);

        $disabled = null;
        $warning = null;
        $info = [];

        if ($email === null) {
            $disabled = 'pas d\'e-mail';
        } elseif ($definition['relaunch']) {
            if (! $engagement?->sent_at) {
                $disabled = 'jamais contacté : faites d\'abord le premier envoi';
            } elseif ($engagement->stage !== EngagementStageEnum::ProposalSent) {
                $disabled = 'étape « '.($engagement->stage?->getLabel() ?? 'inconnue').' » : seuls les clients « Envoyé/Contacté » sont relancés';
            }
        } elseif ($engagement?->sent_at) {
            $warning = 'déjà contacté le '.self::formatDate($engagement->sent_at);
        }

        if ($engagement?->sent_at && ! $warning && ! $disabled) {
            $info[] = 'contacté le '.self::formatDate($engagement->sent_at);
        }
        if ($definition['relaunch'] && $engagement?->relaunched_at && ! $disabled) {
            $info[] = 'dernière relance le '.self::formatDate($engagement->relaunched_at);
        }
        if (! $disabled && $client->previousEditionProvisionElements->isEmpty()) {
            $info[] = 'pas de commande l\'an dernier';
        }

        $description = $disabled
            ? 'Ignoré : '.$disabled
            : '→ '.$email.($info ? ' · '.implode(' · ', $info) : '').($warning ? ' · ⚠ '.$warning : '');

        return [
            'email'       => $email,
            'disabled'    => $disabled,
            'warning'     => $warning,
            'info'        => $info ? implode(' · ', $info) : null,
            'checked'     => $disabled === null && $warning === null,
            'description' => $description,
        ];
    }

    /**
     * Envoie l'email et met à jour le suivi du client (étape « Envoyé/Contacté », date d'envoi ou de relance).
     */
    public static function send(string $key, Client $client): void
    {
        $definition = self::definition($key);
        $class = $definition['notification'];

        $client->notify(new $class($client, $client->getPreviousEditionProvisionElementsDetails()));

        $engagement = $client->currentEngagement()->firstOrCreate([
            'edition_id' => AppHelper::getCurrentEditionId(),
        ]);
        $engagement->stage = EngagementStageEnum::ProposalSent;

        if ($definition['relaunch']) {
            $engagement->status = EngagementStatusEnum::Relaunched;
            $engagement->relaunched_at = now();
        } else {
            $engagement->status = EngagementStatusEnum::Idle;
            $engagement->sent_at = now();
        }

        $engagement->save();
    }
}
