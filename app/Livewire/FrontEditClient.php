<?php

namespace App\Livewire;

use Filament\Forms;
use App\Models\Client;
use Filament\Forms\Get;
use Livewire\Component;
use Filament\Forms\Form;
use App\Models\Provision;
use Illuminate\Support\HtmlString;
use Illuminate\Contracts\View\View;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;

class FrontEditClient extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Client $record;

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Prestations')
                    ->extraAttributes(['class' => '!bg-primary-50'])
                    ->schema([
                        Repeater::make('currentProvisionElements')
                            ->label('')
                            ->relationship()
                            ->addable(false)
                            ->deletable(false)
                            ->columns(2)
                            ->schema([
                                Placeholder::make('provision_indications')
                                    ->label('')
                                    ->content(fn (Model $record) => new HtmlString(
                                        '<div class="font-bold">'.($record->provision?->description ?? $record->provision?->name).'</div>'.
                                        ($record->provision?->dimensions_indicator ? 'Dimensions : '.$record->provision?->dimensions_indicator.'<br>' : null).
                                        ($record->provision?->format_indicator ? 'Format : '.$record->provision?->format_indicator.'<br>' : null).
                                        ($record->provision?->due_date_indicator ? 'Délai : '.$record->provision?->due_date_indicator.'<br>' : null).
                                        ($record->textual_indicator ? 'Mention : '.$record->textual_indicator.'<br>' : null)
                                    )),
                                SpatieMediaLibraryFileUpload::make('medias')
                                    ->label('Ajouter un visuel en respectant le format et les dimensions')
                                    ->collection('provision_elements')
                                    ->multiple()
                                    ->reorderable()
                                    ->openable()
                                    ->downloadable()
                                    ->imagePreviewHeight('50')
                                    ->visible(fn (Model $record) => $record->provision->has_media),
                            ])
                    ]),

                Section::make('Données de contact')
                    ->extraAttributes(['class' => '!bg-gray-50'])
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email'),
                        TextInput::make('invoicing_email')
                            ->label('Email de facturation'),
                    ]),

                Section::make('Personnes de contact')
                    ->schema([
                        Repeater::make('contacts')
                            ->label('')
                            ->relationship('contacts')
                            ->addable(false)
                            ->deletable(false)
                            ->columns(2)
                            ->schema([
                                Placeholder::make('first_name')->label('')->content(fn (Model $record): string => $record->name),
                                TextInput::make('email')->label('')->inlineLabel(),
                                /*
                                TextInput::make('first_name')->disabled()->readOnly()->label('Prénom'),
                                TextInput::make('last_name')->disabled()->readOnly()->label('Nom de famille'),
                                */
                            ]),
                    ]),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->record->update($data);

        Notification::make()
            ->title('Changements enregistrés')
            ->success()
            ->color('success')
            ->send();
    }

    public function render(): View
    {
        return view('livewire.front-edit-client');
    }
}
