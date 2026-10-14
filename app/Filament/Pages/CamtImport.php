<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Exception;
use ZipArchive;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Services\BananaCamtService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;

class CamtImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.camt-import';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?Collection $transactions = null;

    public ?array $summary = null;

    public ?string $tsvContent = null;

    public function mount(): void
    {
        $this->form->fill([
            'bank_account'       => setting('banana_bank_account', '1020'),
            'allocation_mode'    => setting('banana_allocation_mode', 'client_account'),
            'reconcile_invoices' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('camt_files')
                    ->label('Fichiers CAMT UBS (camt.053, camt.054, XML ou ZIP)')
                    ->helperText('Sélectionnez un ou plusieurs fichiers (jusqu\'à 20). Les fichiers .054 (avis QR) et .053 (extraits) sont dédupliqués automatiquement.')
                    ->acceptedFileTypes([
                        'application/xml',
                        'text/xml',
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream',
                    ])
                    ->multiple()
                    ->maxFiles(20)
                    ->storeFiles(false)
                    ->required(),

                Grid::make(3)
                    ->schema([
                        TextInput::make('bank_account')
                            ->label('N° Compte Banque (Banana)')
                            ->helperText('ex. 1020')
                            ->default(fn () => setting('banana_bank_account', '1020'))
                            ->required(),

                        Select::make('allocation_mode')
                            ->label('Affectation des factures QR')
                            ->options([
                                'client_account' => 'Compte Débiteurs (1100) - Sans TVA',
                                'provisions'     => 'Compte prestation & TVA (Encaissements)',
                            ])
                            ->default(fn () => setting('banana_allocation_mode', 'client_account'))
                            ->required(),

                        Toggle::make('reconcile_invoices')
                            ->label('Rapprocher les factures CDN')
                            ->helperText('Marque les factures QR payées en base de données')
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $formData = $this->form->getState();
        $rawFiles = $formData['camt_files'] ?? [];

        if (empty($rawFiles)) {
            Notification::make()
                ->title('Aucun fichier sélectionné')
                ->warning()
                ->send();

            return;
        }

        // Si un seul fichier n'est pas dans un tableau
        if (! is_array($rawFiles)) {
            $rawFiles = [$rawFiles];
        }

        // Traitement éventuel d'archives ZIP
        $extractedFiles = [];
        $tempDir = null;

        foreach ($rawFiles as $file) {
            if ($file instanceof UploadedFile) {
                $clientName = mb_strtolower($file->getClientOriginalName());
                if (str_ends_with($clientName, '.zip')) {
                    $zip = new ZipArchive;
                    if ($zip->open($file->getRealPath()) === true) {
                        if ($tempDir === null) {
                            $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'camt_zip_'.uniqid();
                            @mkdir($tempDir, 0777, true);
                        }
                        $zip->extractTo($tempDir);
                        $zip->close();

                        // Récupérer tous les fichiers XML du dossier dézippé
                        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tempDir));
                        foreach ($iterator as $f) {
                            if ($f->isFile() && in_array(mb_strtolower($f->getExtension()), ['xml', 'camt'])) {
                                $extractedFiles[] = $f->getPathname();
                            }
                        }
                    }
                } else {
                    $extractedFiles[] = $file;
                }
            } elseif (is_string($file)) {
                $extractedFiles[] = $file;
            }
        }

        if (empty($extractedFiles)) {
            Notification::make()
                ->title('Aucun fichier XML exploitable trouvé')
                ->danger()
                ->send();

            return;
        }

        try {
            $service = app(BananaCamtService::class);
            $result = $service->processFiles($extractedFiles, [
                'bank_account'    => $formData['bank_account'] ?? '1020',
                'allocation_mode' => $formData['allocation_mode'] ?? 'client_account',
                'reconcile'       => (bool) ($formData['reconcile_invoices'] ?? true),
            ]);

            $this->transactions = $result['entries'];
            $this->summary = $result['summary'];
            $this->tsvContent = $result['tsv_content'];

            $reconciled = $this->summary['reconciled_invoices'] ?? 0;
            $unmatched = $this->summary['unmatched_payments'] ?? 0;
            $duplicates = $this->summary['duplicate_entries'] ?? 0;

            Notification::make()
                ->title('Traitement terminé avec succès')
                ->body("{$reconciled} factures réconciliées. {$unmatched} paiements hors factures CDN. {$duplicates} doublons UBS ignorés.")
                ->success()
                ->send();

        } catch (Exception $e) {
            Notification::make()
                ->title('Erreur lors du traitement')
                ->body('Une erreur est survenue lors de l\'analyse des fichiers : '.$e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            // Nettoyage dossier ZIP temporaire
            if ($tempDir && is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        }
    }

    public function downloadBananaFile()
    {
        if (empty($this->tsvContent)) {
            Notification::make()
                ->title('Aucune donnée à exporter')
                ->warning()
                ->send();

            return null;
        }

        $fileName = 'banana_import_'.date('Ymd_His').'.txt';
        $content = $this->tsvContent;

        return response()->streamDownload(function () use ($content) {
            // BOM UTF-8 pour ouverture optimale dans Excel et Banana
            echo "\xEF\xBB\xBF";
            echo $content;
        }, $fileName, [
            'Content-Type' => 'text/tab-separated-values; charset=UTF-8',
        ]);
    }

    public function createRuleAction(): Action
    {
        return Action::make('createRule')
            ->label('Mémoriser cette règle')
            ->modalHeading('Créer une règle d\'affectation Banana')
            ->modalDescription('Cette règle sera enregistrée dans les Paramètres et appliquée immédiatement aux écritures ci-dessous.')
            ->modalSubmitActionLabel('Enregistrer et appliquer')
            ->form([
                TextInput::make('keyword')
                    ->label('Mot-clé dans le libellé')
                    ->helperText('Ex: nom du fournisseur, prestataire ou motif récurrent.')
                    ->required(),
                TextInput::make('account')
                    ->label('N° Compte comptable')
                    ->placeholder('ex. 42020, 42080, 42861, 31100...')
                    ->helperText('Comptes fréquents CDN : 42020 (Salaires), 42080 (Frais banque), 2201 (TVA), 42861 (Web), 42060 (Logiciels), 42320 (Encarts), 42171 (Graphisme), 31100 (Sponsors)...')
                    ->required(),
                Select::make('vat_code')
                    ->label('Code TVA Banana (optionnel)')
                    ->options([
                        ''   => 'Aucun code TVA',
                        'F1' => 'F1 (Annonces - 4.50%)',
                        'F2' => 'F2 (Sponsoring - 5.30%)',
                        'F3' => 'F3 (Banderoles/Publicité - 5.30%)',
                        'F4' => 'F4 (Cantine assis/alcool - 5.30%)',
                        'F5' => 'F5 (Tentes - 3.70%)',
                        'F6' => 'F6 (Cantine à emporter - 0.60%)',
                    ])
                    ->nullable(),
            ])
            ->fillForm(function (array $arguments): array {
                return [
                    'keyword'  => $arguments['keyword'] ?? '',
                    'account'  => $arguments['account'] ?? '',
                    'vat_code' => $arguments['vat_code'] ?? '',
                ];
            })
            ->action(function (array $data): void {
                $keyword = trim((string) ($data['keyword'] ?? ''));
                $account = trim((string) ($data['account'] ?? ''));
                $vatCode = trim((string) ($data['vat_code'] ?? ''));

                if ($keyword === '' || $account === '') {
                    return;
                }

                // 1. Sauvegarde dans les Settings
                $rules = (array) setting('banana_rules', []);
                // Remplacer si le mot-clé existe déjà ou ajouter
                $updated = false;
                foreach ($rules as &$rule) {
                    if (mb_strtolower(trim((string) ($rule['keyword'] ?? ''))) === mb_strtolower($keyword)) {
                        $rule['account'] = $account;
                        $rule['vat_code'] = $vatCode;
                        $updated = true;
                        break;
                    }
                }
                if (! $updated) {
                    $rules[] = [
                        'keyword'  => $keyword,
                        'account'  => $account,
                        'vat_code' => $vatCode,
                    ];
                }

                setting(['banana_rules' => $rules]);

                // 2. Ré-application immédiate sur les transactions courantes
                $this->reapplyCurrentRules();

                Notification::make()
                    ->title('Règle enregistrée avec succès')
                    ->body("La règle '{$keyword}' ➔ Compte {$account} a été enregistrée et appliquée à vos écritures.")
                    ->success()
                    ->send();
            });
    }

    public function reapplyCurrentRules(): void
    {
        if (! $this->transactions || $this->transactions->isEmpty()) {
            return;
        }

        $service = app(BananaCamtService::class);
        $rules = (array) setting('banana_rules', []);
        $bankAccount = (string) ($this->data['bank_account'] ?? setting('banana_bank_account', '1020'));
        $clientAccount = (string) setting('banana_client_account', '1100');
        $supplierAccount = (string) setting('banana_supplier_account', '2000');
        $allocationMode = (string) ($this->data['allocation_mode'] ?? setting('banana_allocation_mode', 'client_account'));

        $this->transactions = $service->reapplyRulesToEntries(
            $this->transactions,
            $rules,
            $bankAccount,
            $clientAccount,
            $supplierAccount,
            $allocationMode
        );

        // Régénération dynamique du fichier Banana TSV
        $this->tsvContent = $service->generateBananaTsv($this->transactions);
    }

    public function resetImport(): void
    {
        $this->transactions = null;
        $this->summary = null;
        $this->tsvContent = null;
        $this->form->fill([
            'bank_account'       => setting('banana_bank_account', '1020'),
            'allocation_mode'    => setting('banana_allocation_mode', 'client_account'),
            'reconcile_invoices' => true,
        ]);
    }

    protected function removeDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}
