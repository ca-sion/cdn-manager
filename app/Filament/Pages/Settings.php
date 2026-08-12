<?php

namespace App\Filament\Pages;

use Filament\Schemas\Components\Section;
use Closure;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Models\ProvisionCategory;
use Filament\Forms\Components\Select;
use Outerweb\FilamentSettings\Pages\Settings as BaseSettings;

use Filament\Schemas\Schema;

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
                Section::make('Inscriptions courses')
                    ->schema([
                        Select::make('default_run_school')
                            ->label('Course par défaut : Écoles / Interclasses')
                            ->options(\App\Models\Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('default_run_company')
                            ->label('Course par défaut : Entreprises')
                            ->options(\App\Models\Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('default_run_elite')
                            ->label('Course par défaut : Élite')
                            ->options(\App\Models\Run::all()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                    ]),
            ]);
    }
}
