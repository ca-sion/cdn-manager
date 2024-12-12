<?php

namespace App\Filament\Exports;

use App\Models\ProvisionElement;
use Filament\Actions\Exports\Exporter;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

class ProvisionElementExporter extends Exporter
{
    protected static ?string $model = ProvisionElement::class;

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id'),
            ExportColumn::make('edition_id'),
            ExportColumn::make('provision.name'),
            ExportColumn::make('provision.dimensions_indicator'),
            ExportColumn::make('provision.format_indicator'),
            ExportColumn::make('provision.due_date_indicator'),
            ExportColumn::make('provision.contact_indicator'),
            ExportColumn::make('provision.category.name'),
            ExportColumn::make('recipient_type'),
            ExportColumn::make('recipient_id'),
            ExportColumn::make('recipient.category.name'),
            ExportColumn::make('recipient.name'),
            ExportColumn::make('recipient.long_name'),
            ExportColumn::make('recipient.first_name'),
            ExportColumn::make('recipient.last_name'),
            ExportColumn::make('recipient.address'),
            ExportColumn::make('recipient.address_extension'),
            ExportColumn::make('recipient.locality'),
            ExportColumn::make('recipient.postal_code'),
            ExportColumn::make('recipient.email'),
            ExportColumn::make('recipient.phone'),
            ExportColumn::make('recipient.role'),
            ExportColumn::make('recipient.department'),
            ExportColumn::make('recipient.salutation'),
            ExportColumn::make('recipient.language'),
            ExportColumn::make('recipientContactEmail'),
            ExportColumn::make('recipientVipContactEmail'),
            ExportColumn::make('status')->formatStateUsing(fn ($state): ?string => $state->value),
            ExportColumn::make('secondary_status'),
            ExportColumn::make('due_date'),
            ExportColumn::make('precision'),
            ExportColumn::make('numeric_indicator'),
            ExportColumn::make('textual_indicator'),
            ExportColumn::make('goods_to_be_delivered'),
            ExportColumn::make('contact.name'),
            ExportColumn::make('contact_text'),
            ExportColumn::make('contact_location'),
            ExportColumn::make('contact_date'),
            ExportColumn::make('contact_time'),
            ExportColumn::make('placeholders'),
            ExportColumn::make('medias'),
            ExportColumn::make('media_status')->formatStateUsing(fn ($state): ?string => $state?->value),
            ExportColumn::make('media_file_name')->state(fn (Model $record): ?string => $record->getFirstMedia('*')?->file_name),
            ExportColumn::make('media_url')->state(fn (Model $record): ?string => $record->getFirstMediaUrl('*')),
            ExportColumn::make('responsible'),
            ExportColumn::make('dicastry.name'),
            ExportColumn::make('tracking_status'),
            ExportColumn::make('tracking_date'),
            ExportColumn::make('accreditation_type'),
            ExportColumn::make('has_product'),
            ExportColumn::make('quantity'),
            ExportColumn::make('unit'),
            ExportColumn::make('cost'),
            ExportColumn::make('tax_rate'),
            ExportColumn::make('discount'),
            ExportColumn::make('include_vat'),
            ExportColumn::make('vip_category'),
            ExportColumn::make('vip_invitation_number'),
            ExportColumn::make('vip_response_status'),
            ExportColumn::make('vip_response_status')->name('vip_response_status_cross')->formatStateUsing(fn (?string $state): ?string => $state == true ? 'x' : null),
            ExportColumn::make('vip_guests'),
            ExportColumn::make('order_column'),
            ExportColumn::make('note'),
            ExportColumn::make('content'),
            ExportColumn::make('frontEditLink')->state(fn (Model $record): ?string => $record->client?->frontEditLink),
            ExportColumn::make('pdfLink')->state(fn (Model $record): ?string => $record->client?->pdfLink),
            ExportColumn::make('meta'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your provision element export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
