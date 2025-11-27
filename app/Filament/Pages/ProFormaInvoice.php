<?php

namespace App\Filament\Pages;

use App\Classes\Price;
use App\Models\Client;
use App\Models\Invoice;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\View;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProFormaInvoice extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.pro-forma-invoice';

    protected ?string $heading = 'Générer une facture proforma';

    protected static ?string $navigationGroup = 'Facturation';

    protected static ?string $navigationLabel = 'Facture proforma';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getFormStatePath(): string
    {
        return 'data';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Client')
                    ->columns(2)
                    ->schema([
                        Select::make('client_id')
                            ->label('Client existant')
                            ->options(Client::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('client_name', null);
                                    $set('client_address', null);
                                    $set('client_postal_code', null);
                                    $set('client_locality', null);

                                    return;
                                }
                                $client = Client::find($state);
                                $set('client_name', $client->invoicing_name ?? $client->name);
                                $set('client_address', $client->invoicing_address ?? $client->address);
                                $set('client_postal_code', $client->invoicing_postal_code ?? $client->postal_code);
                                $set('client_locality', $client->invoicing_locality ?? $client->locality);
                            }),
                        Grid::make(1)
                            ->schema([
                                TextInput::make('client_name')->label('Nom du client')->required(),
                                Grid::make(3)->schema([
                                    TextInput::make('client_address')->label('Adresse'),
                                    TextInput::make('client_postal_code')->label('NPA'),
                                    TextInput::make('client_locality')->label('Localité'),
                                ]),
                            ]),
                    ]),

                Section::make('Détails de la facture')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('date')->label('Date')->default(now())->required(),
                        DatePicker::make('due_date')->label('Échéance')->default(now()->addDays(30)),
                        TextInput::make('title')->label('Titre de la facture')->default('Facture proforma')->required(),
                        TextInput::make('number')
                            ->label('Numéro de facture')
                            ->default(fn () => now()->format('YmdHi'))
                            ->hintAction(
                                Action::make('generate')
                                    ->label('Générer')
                                    ->icon('heroicon-m-arrow-path')
                                    ->action(function (Set $set) {
                                        $set('number', now()->format('YmdHi'));
                                    })
                            )
                            ->maxLength(255),
                        TextInput::make('client_reference')
                            ->label('Référence pour le client')
                            ->maxLength(255),
                    ]),

                Section::make('Lignes de la facture')
                    ->schema([
                        Repeater::make('positions')
                            ->label('Lignes')
                            ->columns(7)
                            ->schema([
                                TextInput::make('name')->label('Désignation')->required()->columnSpan(2),
                                TextInput::make('quantity')->label('Quantité')->numeric()->default(1)->required()->live(),
                                TextInput::make('cost')->label('Prix unit.')->numeric()->default(0)->required()->live(),
                                TextInput::make('tax_rate')->label('TVA (%)')->numeric()->default(8.1)->required()->live(),
                                Checkbox::make('include_vat')->label('Inclure TVA')->inline(false)->live(),
                                Placeholder::make('net_amount')
                                    ->label('Total')
                                    ->content(function (Get $get) {
                                        $price = Price::of($get('cost'))->quantity($get('quantity'))->taxRate($get('tax_rate'))->includeTaxInPrice(false);

                                        return $price->amount('c');
                                    }),
                            ])
                            ->live()
                            ->addActionLabel('Ajouter une ligne'),
                    ]),

                Section::make('Informations additionnelles')
                    ->schema([
                        Textarea::make('content')->label('Contenu (optionnel)'),
                        Textarea::make('footer')->label('Pied de page (optionnel)'),
                    ]),

                Actions::make([
                    Action::make('generatePdf')
                        ->label('Générer la facture PDF')
                        ->action('generatePdf'),
                ]),
            ])
            ->statePath('data');
    }

    public function generatePdf(): StreamedResponse
    {
        $data = $this->form->getState();

        // 1. Create an in-memory Invoice object
        $invoice = new Invoice;
        $invoice->is_pro_forma = true;
        $invoice->number = now()->format('Ymd-His');
        $invoice->date = $data['date'];
        $invoice->due_date = $data['due_date'];
        $invoice->title = $data['title'];
        $invoice->number = $data['number'];
        $invoice->client_reference = $data['client_reference'];
        $invoice->content = nl2br($data['content']) ?? null;
        $invoice->footer = nl2br($data['footer']) ?? null;
        $invoice->positions = $data['positions'] ?? [];

        // 2. Create an in-memory Client object
        $client = new Client;
        $client->name = $data['client_name'];
        $client->address = $data['client_address'];
        $client->postal_code = $data['client_postal_code'];
        $client->locality = $data['client_locality'];

        // Use setRelation to attach the in-memory client to the in-memory invoice
        $invoice->setRelation('client', $client);

        // 3. Generate PDF
        $view = View::make('pdf.invoice', ['invoice' => $invoice, 'qrBillOutput' => '']);
        $html = mb_convert_encoding($view, 'HTML-ENTITIES', 'UTF-8');

        $pdf = Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOption(['defaultFont' => 'sans-serif']);

        // 4. Stream the response
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $invoice->number.'.pdf');
    }
}
