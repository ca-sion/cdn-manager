<?php

namespace App\Filament\Pages;

use App\Models\Run;
use App\Models\Edition;
use App\Models\Provision;
use Filament\Schemas\Schema;
use App\Models\ClientCategory;
use App\Models\ProvisionCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Outerweb\FilamentSettings\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    public static function getNavigationLabel(): string
    {
        return 'Paramètres';
    }

    public function getTitle(): string
    {
        return 'Paramètres';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('edition_id')
                    ->options(Edition::all()->pluck('name', 'id'))
                    ->default(config('cdn.default_edition_id')),
                Section::make('Formulaire annonceur')
                    ->schema([
                        Select::make('advertiser_form_client_category')
                            ->label('Catégorie des clients')
                            ->options(ClientCategory::all()->pluck('name', 'id')),
                        Select::make('advertiser_form_journal_category')
                            ->label('Journal : Catégorie des prestations')
                            ->options(ProvisionCategory::all()->pluck('name', 'id')),
                        Select::make('advertiser_form_banner_category')
                            ->label('Banderole : Catégorie des prestations')
                            ->options(ProvisionCategory::all()->pluck('name', 'id')),
                        Select::make('advertiser_form_screen_category')
                            ->label('Ecran : Catégorie des prestations')
                            ->options(ProvisionCategory::all()->pluck('name', 'id')),
                        Select::make('advertiser_form_pack_category')
                            ->label('Packs : Catégorie des prestations')
                            ->options(ProvisionCategory::all()->pluck('name', 'id')),
                        Select::make('advertiser_form_donation_provision')
                            ->label('Donation : Prestation')
                            ->options(Provision::all()->pluck('name', 'id')),
                    ]),
                Section::make('VIP')
                    ->schema([
                        Select::make('vip_provision')
                            ->label('Prestation')
                            ->options(Provision::all()->pluck('name', 'id')),
                    ]),
                Section::make('Rapports')
                    ->schema([
                        Select::make('reports_advertisers_categories')
                            ->label('Catégories des annonceurs')
                            ->options(ClientCategory::all()->pluck('name', 'id'))
                            ->multiple(),
                        Select::make('reports_banners_provisions')
                            ->label('Prestations pour les banderoles')
                            ->options(Provision::all()->pluck('name', 'id'))
                            ->multiple(),
                        Select::make('reports_screens_provisions')
                            ->label('Prestations pour les écrans')
                            ->options(Provision::all()->pluck('name', 'id'))
                            ->multiple(),
                        Select::make('reports_advertisers_journal_provisions')
                            ->label('Prestations pour le journal')
                            ->options(Provision::all()->pluck('name', 'id'))
                            ->multiple(),
                        Select::make('reports_interclass_donor_provision')
                            ->label('Donation interclasse : Prestation')
                            ->options(Provision::all()->pluck('name', 'id')),
                    ]),
                Section::make('Vouchers et inscriptions offertes')
                    ->schema([
                        Select::make('voucher_cdn_provisions')
                            ->label('Prestations : Vouchers Course de Noël (CDN)')
                            ->helperText('Prestations donnant droit à des codes vouchers pour la Course de Noël.')
                            ->options(Provision::all()->pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),
                        Select::make('voucher_trail_provisions')
                            ->label('Prestations : Inscriptions Trail des Châteaux')
                            ->helperText('Prestations donnant droit à des inscriptions offertes pour le Trail des Châteaux (contact par email).')
                            ->options(Provision::all()->pluck('name', 'id'))
                            ->multiple()
                            ->searchable(),
                    ]),
                Section::make('Inscriptions courses')
                    ->schema([
                        Select::make('default_run_school')
                            ->label('Course par défaut : Écoles / Interclasses')
                            ->options(Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('default_run_company')
                            ->label('Course par défaut : Entreprises')
                            ->options(Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('default_run_elite')
                            ->label('Course par défaut : Élite')
                            ->options(Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ]),
                Section::make('Comptabilité & Banana')
                    ->schema([
                        TextInput::make('banana_bank_account')
                            ->label('Compte bancaire (Banana)')
                            ->helperText('Numéro du compte banque au bilan (ex. 1020). Débit pour encaissement, Crédit pour décaissement.')
                            ->default('1020')
                            ->required(),
                        TextInput::make('banana_client_account')
                            ->label('Compte Débiteurs / Clients par défaut')
                            ->helperText('Compte de contrepartie par défaut pour les paiements de factures QR (ex. 1100 ou 31100).')
                            ->default('1100')
                            ->required(),
                        TextInput::make('banana_supplier_account')
                            ->label('Compte Créanciers / Fournisseurs par défaut')
                            ->helperText('Compte au débit par défaut pour les décaissements sans règle spécifique (ex. 2000 ou 42021).')
                            ->default('2000')
                            ->required(),
                        Select::make('banana_allocation_mode')
                            ->label('Mode d\'affectation des factures QR')
                            ->options([
                                'client_account' => 'Compte Débiteurs général (ex: 1100) - Sans TVA sur le relevé',
                                'provisions'     => 'Compte de prestation & code TVA (Comptabilité aux encaissements)',
                            ])
                            ->default('client_account')
                            ->helperText('Si vous gérez les débiteurs dans Banana, choisissez "Compte Débiteurs" (la TVA a déjà été enregistrée sur la facture). Si vous comptabilisez au paiement, choisissez "Compte de prestation & TVA".')
                            ->required(),
                        Repeater::make('banana_rules')
                            ->label('Règles d\'affectation automatique par mots-clés')
                            ->helperText('Appliquées si le libellé de la transaction contient le mot-clé (priorité aux paiements hors factures QR).')
                            ->schema([
                                TextInput::make('keyword')
                                    ->label('Mot-clé dans le libellé')
                                    ->placeholder('ex. Salaire, Migros, AFC')
                                    ->required(),
                                TextInput::make('account')
                                    ->label('Numéro de compte')
                                    ->placeholder('ex. 5000, 4200, 2200')
                                    ->required(),
                                TextInput::make('vat_code')
                                    ->label('Code TVA Banana (optionnel)')
                                    ->placeholder('ex. USt81 ou VS81'),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->collapsible(),
                    ]),
            ]);
    }
}
