<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use App\Models\RunRegistration;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\RunRegistrationResource;

/**
 * Définition unique des exports d'inscriptions (Datasport, récapitulatifs) :
 * chaque export est disponible en action d'en-tête (tout / par type) et en action groupée (sélection).
 */
class RegistrationExportActions
{
    /**
     * @return array<string, array{label: string, icon: string, color: string, type: ?string, with: array, export: Closure}>
     */
    protected static function definitions(): array
    {
        return [
            'exportDatasportSchool' => [
                'label'  => 'Export Datasport interclasses', 'icon' => 'heroicon-o-academic-cap', 'color' => 'info',
                'type'   => 'school', 'with' => ['runRegistrationElements.run'],
                'export' => fn ($rows) => RunRegistrationResource::generateDatasportSchoolExcel($rows),
            ],
            'exportSchoolClasses' => [
                'label'  => 'Export classes interclasses', 'icon' => 'heroicon-o-table-cells', 'color' => 'info',
                'type'   => 'school', 'with' => ['runRegistrationElements'],
                'export' => fn ($rows) => RunRegistrationResource::generateSchoolClassesExcel($rows),
            ],
            'exportDatasportCompany' => [
                'label'  => 'Export Datasport entreprises', 'icon' => 'heroicon-o-building-office', 'color' => 'warning',
                'type'   => 'company', 'with' => ['runRegistrationElements.run'],
                'export' => fn ($rows) => RunRegistrationResource::generateDatasportCompanyExcel($rows),
            ],
            'exportDatasportGroup' => [
                'label'  => 'Export Datasport groupes', 'icon' => 'heroicon-o-user-group', 'color' => 'gray',
                'type'   => 'group', 'with' => ['runRegistrationElements.run'],
                'export' => fn ($rows) => RunRegistrationResource::generateDatasportGroupExcel($rows),
            ],
            'exportAggregatedData' => [
                'label'  => 'Export récapitulatif comptable', 'icon' => 'heroicon-o-chart-bar', 'color' => 'success',
                'type'   => null, 'with' => ['runRegistrationElements.run.provision.product', 'client', 'invoice'],
                'export' => fn ($rows) => RunRegistrationResource::generateAggregatedExcel($rows),
            ],
            'exportDetailedParticipants' => [
                'label'  => 'Export participants détaillé', 'icon' => 'heroicon-o-users', 'color' => 'primary',
                'type'   => null, 'with' => ['runRegistrationElements.run.provision.product', 'client', 'invoice'],
                'export' => fn ($rows) => RunRegistrationResource::generateDetailedParticipantsExcel($rows),
            ],
        ];
    }

    /** @return array<int, Action> */
    public static function header(): array
    {
        return collect(self::definitions())->map(fn (array $d, string $name) => Action::make($name)
            ->label($d['label'])
            ->icon($d['icon'])
            ->color($d['color'])
            ->action(function () use ($d) {
                $query = RunRegistration::query()->with($d['with']);
                if ($d['type']) {
                    $query->where('run_registration_type', $d['type']);
                }

                return ($d['export'])($query->get());
            }))->values()->all();
    }

    /** @return array<int, BulkAction> */
    public static function bulk(): array
    {
        return collect(self::definitions())->map(fn (array $d, string $name) => BulkAction::make($name)
            ->label($d['label'].' (sélection)')
            ->icon($d['icon'])
            ->action(function (Collection $records) use ($d) {
                if ($d['type']) {
                    $records = $records->filter(fn ($r) => ($r->run_registration_type?->value ?? $r->run_registration_type) === $d['type']);
                }

                return ($d['export'])($records);
            }))->values()->all();
    }
}
