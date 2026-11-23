<?php

namespace App\Filament\Actions;

use Throwable;
use App\Models\Client;
use App\Models\ProvisionElement;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use App\Enums\ProvisionElementStatusEnum;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\BulkMailReport;
use App\Notifications\RecipientSendVipInvitation;
use App\Filament\Actions\Support\BulkInvoicePreview;

/**
 * Envoie les invitations VIP avec aperçu avant envoi et compte rendu détaillé :
 * chaque destinataire est listé avec son adresse e-mail (et CC éventuel), sa catégorie/nombre de places
 * et les avertissements (déjà envoyé, déjà répondu) ; ceux sans prestation VIP ou sans e-mail sont désactivés.
 *
 * - Par défaut, les enregistrements sont des prestations VIP (ProvisionElement).
 * - Avec ->forRecipients(), ce sont des destinataires (Client / Contact) : leur prestation VIP
 *   de l'édition courante est retrouvée automatiquement.
 */
class SendVipInvitationBulkAction extends BulkAction
{
    protected bool $forRecipients = false;

    public static function getDefaultName(): ?string
    {
        return 'sendVipInvitation';
    }

    public function forRecipients(bool $forRecipients = true): static
    {
        $this->forRecipients = $forRecipients;

        return $this;
    }

    /**
     * Évalue l'éligibilité et les informations d'aperçu d'un enregistrement.
     *
     * @return array{label: string, element: ?ProvisionElement, notifiable: mixed, email: ?string, disabled: ?string, warning: ?string, checked: bool, description: string}
     */
    protected function assess(mixed $record, int $vipProvisionId): array
    {
        if ($this->forRecipients) {
            $element = $record->currentProvisionElements()->where('provision_id', $vipProvisionId)->first();
            $email = $record instanceof Client ? $record->vipContactEmail : $record->email;
            $notifiable = $record;
            $label = $record->name ?? 'Destinataire #'.$record->getKey();
        } else {
            $element = $record->provision_id == $vipProvisionId ? $record : null;
            $email = $record->recipientVipContactEmail;
            $notifiable = $record->recipient;
            $label = $record->recipient?->name ?? 'Destinataire #'.$record->getKey();
        }

        $email = filled($email) ? (string) $email : null;
        $disabled = null;
        $warning = null;
        $info = [];

        if (! $element) {
            $disabled = $this->forRecipients ? 'pas de prestation VIP pour cette édition' : 'n\'est pas une prestation VIP';
        } elseif ($email === null) {
            $disabled = 'pas d\'e-mail VIP';
        } else {
            if ($element->vip_category) {
                $info[] = $element->vip_category;
            }
            if ($element->vip_invitation_number) {
                $info[] = $element->vip_invitation_number.' place(s)';
            }

            if ($element->vip_response_status !== null) {
                $warning = 'déjà répondu (« '.((bool) $element->vip_response_status ? 'Présent' : 'Absent').' »)';
            } elseif ($element->status === ProvisionElementStatusEnum::Sent) {
                $warning = 'déjà envoyé';
            } elseif ($element->status === ProvisionElementStatusEnum::Cancelled) {
                $warning = 'prestation annulée';
            }
        }

        $cc = $element?->recipientContactEmail;
        $ccSuffix = ($cc && $email && $cc !== $email) ? ' (CC : '.$cc.')' : '';

        $description = $disabled
            ? 'Ignoré : '.$disabled
            : '→ '.$email.$ccSuffix.($info ? ' · '.implode(' · ', $info) : '').($warning ? ' · ⚠ '.$warning : '');

        return [
            'label'       => $label,
            'element'     => $element,
            'notifiable'  => $notifiable,
            'email'       => $email,
            'disabled'    => $disabled,
            'warning'     => $warning,
            'checked'     => $disabled === null && $warning === null,
            'description' => $description,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer invitations VIP')
            ->icon('heroicon-m-envelope')
            ->color('warning')
            ->requiresConfirmation(false)
            ->modalHeading('Envoyer les invitations VIP')
            ->modalDescription('Vérifiez la liste : seuls les destinataires cochés recevront l\'invitation VIP. Rien n\'est envoyé avant de valider, et un email envoyé ne peut pas être annulé.')
            ->modalSubmitActionLabel('Envoyer aux destinataires cochés')
            ->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $vipProvisionId = (int) setting('vip_provision');
                $preview = ['options' => [], 'descriptions' => [], 'default' => [], 'disabled' => [], 'counts' => []];
                $counts = ['send' => 0, 'warning' => 0, 'ignored' => 0];

                foreach ($records as $record) {
                    $id = (int) $record->getKey();
                    $assessment = $this->assess($record, $vipProvisionId);

                    $preview['options'][$id] = $assessment['label'];
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

                $components = [
                    Text::make($records->count().' sélectionné(s) · '.$counts['send'].' à envoyer · '.$counts['warning'].' déjà envoyé(s)/répondu(s) (décoché(s)) · '.$counts['ignored'].' ignoré(s)')->weight('bold'),
                ];

                if ($list['options'] !== []) {
                    $components[] = CheckboxList::make('record_ids')
                        ->label('Destinataires à contacter')
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

                return $components;
            })
            ->action(function (Collection $records, array $data) {
                $vipProvisionId = (int) setting('vip_provision');
                $report = new BulkMailReport('Envoi des invitations VIP');
                $selected = collect($data['record_ids'] ?? [])->map(fn ($id) => (int) $id);

                foreach ($records as $record) {
                    $id = (int) $record->getKey();
                    $assessment = $this->assess($record, $vipProvisionId);

                    if (! $selected->contains($id)) {
                        $report->skipped($assessment['label'], 'non coché');

                        continue;
                    }

                    if ($assessment['disabled']) {
                        $report->skipped($assessment['label'], $assessment['disabled']);

                        continue;
                    }

                    try {
                        $assessment['notifiable']->notify(new RecipientSendVipInvitation($assessment['element']));
                        $assessment['element']->status = ProvisionElementStatusEnum::Sent;
                        $assessment['element']->save();
                        $report->sent($assessment['label'], $assessment['email']);
                    } catch (Throwable $e) {
                        report($e);
                        $report->failed($assessment['label'], $e->getMessage());
                    }
                }

                $report->send();
            });
    }
}
