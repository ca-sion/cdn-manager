<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use Genkgo\Camt\Config;
use Genkgo\Camt\Reader;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;

class CamtImport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.camt-import';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?Collection $transactions;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('camt_file')
                    ->label('Fichier CAMT.054 (XML)')
                    ->acceptedFileTypes(['application/xml', 'text/xml'])
                    ->storeFiles(false)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $file = $data['camt_file'];

        // Initialisation de la librairie Genkgo/Camt
        $reader = new Reader(Config::getDefault());

        try {
            $message = $reader->readFile($file->getRealPath());
            $notifications = $message->getRecords();

            $this->transactions = collect();

            $reconciledCount = 0;
            $notFoundCount = 0;

            DB::beginTransaction();

            foreach ($notifications as $notification) {
                foreach ($notification->getEntries() as $entry) {
                    if ($entry->getCreditDebitIndicator() == 'CRDT') {

                        $transaction = $entry->getTransactionDetail();
                        $qrReference = $transaction?->getRemittanceInformation()?->getCreditorReferenceInformation()?->getRef();
                        $bookingDate = Carbon::parse($entry->getBookingDate());
                        $transactionReference = $transaction?->getReference()?->getAccountServicerReference();
                        $transactionAmount = $transaction?->getAmount()?->getAmount() / 100;
                        $debtor = $transaction?->getRelatedParty()?->getRelatedPartyType()?->getName();

                        if ($qrReference) {
                            $invoice = Invoice::where('qr_reference', $qrReference)->first();

                            if ($invoice) {
                                if ($invoice->total != $transactionAmount) {
                                    $invoiceStatus = InvoiceStatusEnum::ActionRequired->value;

                                    Notification::make()
                                        ->title('Montant inexact')
                                        ->body("Montant payé de la facture #{$invoice->number} inexact.")
                                        ->warning()
                                        ->persistent()
                                        ->send();
                                } else {
                                    $invoiceStatus = InvoiceStatusEnum::Payed->value;
                                }

                                $invoice->status = $invoiceStatus;
                                $invoice->paid_on = $bookingDate;
                                $invoice->reference = empty($invoice->reference) ? $transactionReference : $invoice->reference;
                                $invoice->save();

                                $reconciledCount++;
                            } else {
                                $notFoundCount++;
                            }
                        }

                        // For table
                        $this->transactions->add((object) [
                            'debtor'       => $debtor,
                            'qr_reference' => $qrReference,
                            'invoice'      => $invoice ?? null,
                            'date'         => $bookingDate,
                            'amount'       => $transactionAmount,
                            'reference'    => $transactionReference,
                        ]);
                    }
                }
            }

            DB::commit();

            // Afficher une notification de succès
            Notification::make()
                ->title('Importation réussie')
                ->body("{$reconciledCount} factures ont été rapprochées avec succès. {$notFoundCount} paiements n'ont pas trouvé de facture correspondante.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Erreur lors de l\'importation')
                ->body('Veuillez vérifier que le fichier est au format CAMT.054 valide. Erreur :'.$e->getMessage())
                ->danger()
                ->send();
        }

        $this->form->fill();
    }
}
