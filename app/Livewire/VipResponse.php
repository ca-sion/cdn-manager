<?php

namespace App\Livewire;

use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;

class VipResponse extends Component implements HasForms
{
    use InteractsWithForms;

    public ProvisionElement $provisionElement;

    public ?array $data = [];

    public function mount(ProvisionElement $provisionElement): void
    {
        $this->provisionElement = $provisionElement;
        $this->form->fill($provisionElement->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->description(new HtmlString(''))
                    ->columns(3)
                    ->schema([
                        Placeholder::make('details')
                            ->label(new HtmlString('<div class="format"><h2>Votre invitation VIP</h2></div>'))
                            ->content(function (Get $get) {
                                return new HtmlString('<div class="format">
                                Pour confirmer votre invitation VIP à la Course de Noël et au Trail des Châteaux 2023, veuillez renseigner les informations ci-après. Vous pouvez également indiquer le nom des personnes invitées selon le nombre d\'invitations autorisées.
                                </div>');
                            })
                            ->columnSpanFull(),
                        Placeholder::make('vip_invitation_number')
                            ->label('Nombre d\'invitations')
                            ->content(fn () => $this->provisionElement->vip_invitation_number),
                        Placeholder::make('invited')
                            ->label('Invité')
                            ->content(fn () => $this->provisionElement->recipient->name),
                        Placeholder::make('category')
                            ->label('Type d\'invitation')
                            ->content(fn () => $this->provisionElement->vip_category),
                    ]),
                Section::make()
                    ->description(new HtmlString(''))
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('vip_response_status')
                            ->label('Votre réponse')
                            ->default(null)
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
                            ->nestedRecursiveRules([
                                'min:3',
                                'max:255',
                            ])
                            ->hint('Appuyer sur `,` ou `enter` pour ajouter un nom')
                            ->helperText(fn () => 'Vous avez le droit à '.$this->provisionElement->vip_invitation_number.' invités y compris vous-même.')
                            ->visible(fn (Get $get) => $get('vip_response_status') && $this->provisionElement->vip_invitation_number > 1),
                        Textarea::make('note')
                            ->label('Remarques')
                            ->maxLength(40000)
                            ->autosize()
                            ->columnSpanFull(),
                    ]),

            ])
            ->statePath('data')
            ->model($this->provisionElement);
    }

    public function update()
    {
        $data = $this->form->getState();
        $recipient = $this->provisionElement->recipient;

        $this->provisionElement->vip_response_status = data_get($data, 'vip_response_status');
        $this->provisionElement->vip_guests = data_get($data, 'vip_guests');
        $this->provisionElement->note = data_get($data, 'note');
        $this->provisionElement->save();

        // Email
        //$recipient->notify(new VipResponseUpdated());

        // Notification
        Notification::make()
            ->title('Magnifique !')
            ->body('Merci beaucoup, votre réponse a été sauvegardée.')
            ->success()
            ->send();
    }

    public function render(): View
    {
        return view('livewire.vip-response');
    }
}
