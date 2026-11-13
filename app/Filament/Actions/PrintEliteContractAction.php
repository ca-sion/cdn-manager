<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use App\Models\RunRegistration;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

/**
 * Imprime le contrat Élite (PDF) d'une inscription.
 * Par défaut l'enregistrement est l'inscription ; ->registrationUsing() permet de passer
 * par une relation (ex. RunRegistrationElement::runRegistration).
 */
class PrintEliteContractAction extends Action
{
    protected ?Closure $registrationResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'printEliteContract';
    }

    public function registrationUsing(?Closure $callback): static
    {
        $this->registrationResolver = $callback;

        return $this;
    }

    protected function resolveRegistration(?Model $record): ?RunRegistration
    {
        $registration = ($record && $this->registrationResolver) ? ($this->registrationResolver)($record) : $record;

        return $registration instanceof RunRegistration ? $registration : null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Imprimer Contrat Élite (PDF)')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->openUrlInNewTab()
            ->url(function (?Model $record): ?string {
                $registration = $this->resolveRegistration($record);

                return $registration
                    ? URL::signedRoute('pdf.elite-contract', ['registration' => $registration->id])
                    : null;
            })
            ->visible(function (?Model $record): bool {
                $type = $this->resolveRegistration($record)?->run_registration_type;

                return ($type?->value ?? $type) === 'elite';
            });
    }
}
