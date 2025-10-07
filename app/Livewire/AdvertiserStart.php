<?php

namespace App\Livewire;

use App\Models\Client;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;

class AdvertiserStart extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ToggleButtons::make('choice')
                    ->label('')
                    ->inline()
                    ->options([
                        'first'   => 'Non, c\'est la première fois que je passe commande',
                        'already' => 'Oui, j\'ai déjà passé commande pour une édition précédente',
                    ])
                    ->live(),
                Select::make('client_id')
                    ->label('Rechercher votre société ou entreprise')
                    ->options(Client::all()->pluck('name', 'id'))
                    ->searchable()
                    ->placeholder('Taper le nom de votre entreprise…')
                    ->optionsLimit(1)
                    ->visible(fn (Get $get) => $get('choice') == 'already')
                    ->required(fn (Get $get) => $get('choice') == 'already'),
            ])
            ->statePath('data');
    }

    public function create()
    {
        $data = $this->form->getState();
        $client = Client::find(data_get($data, 'client_id'));

        if ($client) {
            return redirect()->to(URL::signedRoute('advertisers.form.client', ['client' => $client]));
        }

        return redirect()->to(URL::signedRoute('advertisers.form'));
    }

    public function render(): View
    {
        return view('livewire.advertiser-start');
    }
}
