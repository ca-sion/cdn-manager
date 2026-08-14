<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\TextSize;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class FrontEditClient extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public Client $record;

    public function mount(): void
    {
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prestations')
                    ->icon('heroicon-m-shopping-bag')
                    ->schema([
                        Repeater::make('currentProvisionElements')
                            ->label('')
                            ->hiddenLabel()
                            ->relationship()
                            ->addable(false)
                            ->deletable(false)
                            ->grid(2)
                            ->schema([
                                TextEntry::make('provision_title')
                                    ->label('Prestation')
                                    ->hiddenLabel()
                                    ->state(fn (Model $record) => $record->provision?->description ?? $record->provision?->name)
                                    ->weight(FontWeight::Bold)
                                    ->size(TextSize::Medium)
                                    ->columnSpanFull(),
                                TextEntry::make('provision_dimensions')
                                    ->label('Dimensions')
                                    ->inlineLabel()
                                    ->state(fn (Model $record) => $record->provision?->dimensions_indicator)
                                    ->icon('heroicon-m-arrows-pointing-out')
                                    ->visible(fn (Model $record) => ! empty($record->provision?->dimensions_indicator)),
                                TextEntry::make('provision_format')
                                    ->label('Format')
                                    ->inlineLabel()
                                    ->state(fn (Model $record) => $record->provision?->format_indicator)
                                    ->icon('heroicon-m-document-text')
                                    ->visible(fn (Model $record) => ! empty($record->provision?->format_indicator)),
                                TextEntry::make('provision_due_date')
                                    ->label('Délai')
                                    ->inlineLabel()
                                    ->state(fn (Model $record) => $record->provision?->due_date_indicator)
                                    ->icon('heroicon-m-clock')
                                    ->visible(fn (Model $record) => ! empty($record->provision?->due_date_indicator)),
                                TextEntry::make('textual_indicator')
                                    ->label('Mention')
                                    ->inlineLabel()
                                    ->state(fn (Model $record) => $record->textual_indicator)
                                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                                    ->visible(fn (Model $record) => ! empty($record->textual_indicator)),
                                SpatieMediaLibraryFileUpload::make('medias')
                                    ->label('Ajouter un visuel en respectant le format et les dimensions')
                                    ->collection('provision_elements')
                                    ->multiple()
                                    ->reorderable()
                                    ->openable()
                                    ->downloadable()
                                    ->imagePreviewHeight('50')
                                    ->columnSpanFull()
                                    ->visible(fn (Model $record) => $record->provision?->has_media),
                            ]),
                    ]),

                Section::make('Données de contact')
                    ->icon('heroicon-m-building-office')
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email(),
                        TextInput::make('invoicing_email')
                            ->label('Email de facturation')
                            ->email(),
                    ]),

                Section::make('Personnes de contact')
                    ->icon('heroicon-m-user-group')
                    ->schema([
                        Repeater::make('contacts')
                            ->label('')
                            ->hiddenLabel()
                            ->relationship('contacts')
                            ->addable(false)
                            ->deletable(false)
                            ->columns(2)
                            ->schema([
                                TextEntry::make('first_name')
                                    ->label('Nom du contact')
                                    ->hiddenLabel()
                                    ->state(fn (Model $record): string => $record->name)
                                    ->icon('heroicon-m-user')
                                    ->weight(FontWeight::Bold),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->hiddenLabel()
                                    ->email()
                                    ->prefixIcon('heroicon-m-envelope'),
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
