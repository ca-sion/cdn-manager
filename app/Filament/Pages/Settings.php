<?php

namespace App\Filament\Pages;

use Closure;
use App\Models\Edition;
use App\Models\Provision;
use App\Models\ClientCategory;
use App\Models\ProvisionCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

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

    public function schema(): array|Closure
    {
        return [
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
                    Select::make('reports_advertisers_journal_provisions')
                        ->label('Prestations pour le journal')
                        ->options(Provision::all()->pluck('name', 'id'))
                        ->multiple(),
                    Select::make('reports_interclass_donor_provision')
                        ->label('Donation interclasse : Prestation')
                        ->options(Provision::all()->pluck('name', 'id')),
                ]),
        ];
    }
}
