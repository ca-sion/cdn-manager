<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Support\MediaStream;

class ExportMediaBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'exportMedias';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Exporter les médias (.zip)')
            ->icon('heroicon-o-document-duplicate')
            ->action(function (Collection $records) {
                $downloads = $records->map(function ($record) {
                    $media = $record->getMedia('provision_elements')->first();
                    if ($media) {
                        $mediaName = $record->recipient?->name ?? $media->name;
                        $media->file_name = str()->slug($mediaName).'-'.$media->id.'.'.pathinfo($media->file_name, PATHINFO_EXTENSION);
                        $media->save();

                        return $media;
                    }

                    return null;
                });
                $downloads = $downloads->filter();

                return MediaStream::create('medias.zip')->addMedia($downloads);
            });
    }
}
