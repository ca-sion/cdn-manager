<?php

declare(strict_types=1);

namespace App\Livewire;

use Throwable;
use Livewire\Component;
use Filament\Schemas\Schema;
use App\Models\ProvisionElement;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\TextSize;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use App\Notifications\VipResponseUpdated;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Facades\RateLimiter;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;

class VipResponse extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ProvisionElement $provisionElement;

    public ?array $data = [];

    public bool $saved = false;

    public ?string $confirmationEmail = null;

    public function mount(ProvisionElement $provisionElement): void
    {
        $this->provisionElement = $provisionElement;

        $initialData = $provisionElement->toArray();
        $initialData['vip_response_status'] = $this->normalizeStatus($provisionElement->vip_response_status);

        $this->form->fill($initialData);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Votre invitation VIP')
                    ->icon('heroicon-m-sparkles')
                    ->description('Pour confirmer votre invitation VIP à la Course de Noël et au Trail des Châteaux, veuillez renseigner les informations ci-après. Vous pouvez également indiquer le nom des personnes invitées selon le nombre d\'invitations autorisées.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('vip_invitation_number')
                            ->label('Nombre d\'invitations')
                            ->state(fn () => $this->provisionElement->vip_invitation_number ?? 1)
                            ->icon('heroicon-m-ticket')
                            ->badge()
                            ->color('primary')
                            ->size(TextSize::Large),
                        TextEntry::make('invited')
                            ->label('Invité')
                            ->state(fn () => $this->provisionElement->recipient?->name)
                            ->icon('heroicon-m-user')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('category')
                            ->label('Type d\'invitation')
                            ->state(fn () => $this->provisionElement->vip_category_label)
                            ->icon('heroicon-m-tag')
                            ->badge()
                            ->color('info'),
                    ]),
                Section::make('Réponse')
                    ->icon('heroicon-m-envelope-open')
                    ->schema([
                        ToggleButtons::make('vip_response_status')
                            ->label('Votre réponse')
                            ->default(null)
                            ->required()
                            ->boolean()
                            ->inline()
                            ->live()
                            ->options([
                                0 => 'Absent',
                                1 => 'Présent',
                            ])
                            ->icons([
                                0 => 'heroicon-m-x-circle',
                                1 => 'heroicon-m-hand-thumb-up',
                            ])
                            ->colors([
                                0 => 'danger',
                                1 => 'success',
                            ]),
                        TagsInput::make('vip_guests')
                            ->label('Noms des invités')
                            ->splitKeys([',', 'Tab'])
                            ->placeholder('Ajouter un invité (Prénom et nom de famille)')
                            ->reorderable()
                            ->live()
                            ->nestedRecursiveRules([
                                'min:3',
                                'max:255',
                            ])
                            ->hint('Validez chaque nom avec Entrée ou une virgule')
                            ->helperText(fn () => 'Vous avez droit à '.$this->provisionElement->vip_invitation_number.' places au total, vous y compris.')
                            ->visible(fn (Get $get) => (bool) $get('vip_response_status') && $this->provisionElement->vip_invitation_number > 1),
                        Textarea::make('note')
                            ->label('Remarques')
                            ->placeholder('Allergies alimentaires, heure d\'arrivée estimée ou toute autre précision...')
                            ->maxLength(40000)
                            ->autosize()
                            ->live(debounce: 300),
                    ]),

            ])
            ->statePath('data')
            ->model($this->provisionElement);
    }

    public function hasValidStatus(): bool
    {
        return $this->normalizeStatus(data_get($this->data, 'vip_response_status')) !== null;
    }

    public function hasChanges(?array $state = null): bool
    {
        $source = $state ?? $this->data;

        $currentStatus = $this->normalizeStatus($this->provisionElement->vip_response_status);
        $newStatus = $this->normalizeStatus(data_get($source, 'vip_response_status'));

        $currentGuests = $this->normalizeGuests($this->provisionElement->vip_guests);
        $newGuests = $this->normalizeGuests(data_get($source, 'vip_guests'));

        $currentNote = $this->normalizeNote($this->provisionElement->note);
        $newNote = $this->normalizeNote(data_get($source, 'note'));

        return $currentStatus !== $newStatus
            || $currentGuests !== $newGuests
            || $currentNote !== $newNote;
    }

    public function update(): void
    {
        $data = $this->form->getState();

        if (! $this->hasChanges($data)) {
            Notification::make()
                ->title('Aucune modification')
                ->body('Votre réponse est déjà à jour, aucun e-mail n\'a été envoyé.')
                ->info()
                ->send();

            return;
        }

        $rateLimitKey = 'vip-response:'.$this->provisionElement->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            Notification::make()
                ->title('Trop de tentatives')
                ->body('Veuillez patienter '.$seconds.' secondes avant de modifier à nouveau votre réponse.')
                ->warning()
                ->send();

            return;
        }

        RateLimiter::hit($rateLimitKey, 300);

        $recipient = $this->provisionElement->recipient;

        $this->provisionElement->vip_response_status = data_get($data, 'vip_response_status');
        $this->provisionElement->vip_guests = $this->normalizeGuests(data_get($data, 'vip_guests'));
        $this->provisionElement->note = $this->normalizeNote(data_get($data, 'note'));
        $this->provisionElement->save();

        $emailSentTo = null;
        $recipientEmail = $this->provisionElement->recipientVipContactEmail ?? $this->provisionElement->recipientContactEmail;

        if ($recipient && filled($recipientEmail)) {
            try {
                $recipient->notify(new VipResponseUpdated($this->provisionElement));
                $emailSentTo = (string) $recipientEmail;
            } catch (Throwable $e) {
                report($e);
            }
        }

        $this->saved = true;
        $this->confirmationEmail = $emailSentTo;

        $body = 'Merci beaucoup, votre réponse a été sauvegardée.';
        if ($emailSentTo) {
            $body .= ' Un e-mail de confirmation a été envoyé à '.$emailSentTo.'.';
        }

        Notification::make()
            ->title('Magnifique !')
            ->body($body)
            ->success()
            ->send();
    }

    private function normalizeStatus(mixed $value): ?bool
    {
        if ($value === null || $value === '' || $value === 'pending') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return match ($value) {
            1, '1', 'true'  => true,
            0, '0', 'false' => false,
            default         => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function normalizeGuests(mixed $value): array
    {
        return collect($value ?? [])
            ->map(fn (mixed $guest): string => trim((string) $guest))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeNote(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }

    public function render(): View
    {
        return view('livewire.vip-response');
    }
}
