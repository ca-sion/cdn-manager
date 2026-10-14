<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use SimpleXMLElement;
use App\Models\Invoice;
use App\Models\Product;
use App\Enums\InvoiceStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BananaCamtService
{
    /**
     * Traite un ensemble de fichiers CAMT (chemins ou UploadedFile).
     *
     * @param  array<UploadedFile|string>  $files
     * @param  array<string, mixed>  $options
     * @return array{
     *     entries: Collection<int, array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     tsv_content: string
     * }
     */
    public function processFiles(array $files, array $options = []): array
    {
        $getSetting = function (string $key, mixed $default) {
            try {
                return function_exists('setting') ? (setting($key) ?? $default) : $default;
            } catch (\Throwable) {
                return $default;
            }
        };

        $bankAccount = (string) ($options['bank_account'] ?? $getSetting('banana_bank_account', '1020'));
        $defaultClientAccount = (string) ($options['client_account'] ?? $getSetting('banana_client_account', '1100'));
        $defaultSupplierAccount = (string) ($options['supplier_account'] ?? $getSetting('banana_supplier_account', '2000'));
        $allocationMode = (string) ($options['allocation_mode'] ?? $getSetting('banana_allocation_mode', 'client_account'));
        $rules = (array) ($options['rules'] ?? $getSetting('banana_rules', []));
        $reconcileInDatabase = (bool) ($options['reconcile'] ?? true);

        // 1. Trier les fichiers : camt.054 en premier, puis camt.053
        $sortedFiles = $this->sortFilesByPriority($files);

        $processedSignatures = [];
        $entries = collect();

        $stats = [
            'files_count'         => count($sortedFiles),
            'total_transactions'  => 0,
            'reconciled_invoices' => 0,
            'unmatched_payments'  => 0,
            'mismatched_amounts'  => 0,
            'other_transactions'  => 0,
            'duplicate_entries'   => 0,
            'total_credit'        => 0.0,
            'total_debit'         => 0.0,
        ];

        DB::beginTransaction();

        try {
            foreach ($sortedFiles as $file) {
                $fileContent = $this->getFileContent($file);
                if (empty($fileContent)) {
                    continue;
                }

                $fileEntries = $this->parseCamtXml($fileContent, $processedSignatures, $stats);

                foreach ($fileEntries as $entry) {
                    $enriched = $this->enrichAndReconcileEntry(
                        $entry,
                        $bankAccount,
                        $defaultClientAccount,
                        $defaultSupplierAccount,
                        $allocationMode,
                        $rules,
                        $reconcileInDatabase,
                        $stats
                    );

                    $entries->push($enriched);

                    if ($enriched['sens'] === 'CRDT') {
                        $stats['total_credit'] += $enriched['amount'];
                    } else {
                        $stats['total_debit'] += $enriched['amount'];
                    }
                }
            }

            if ($reconcileInDatabase) {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Tri chronologique des écritures
        $sortedEntries = $entries->sortBy(fn (array $item) => $item['raw_date'].' '.$item['external_reference'])->values();

        // Génération du contenu TSV pour Banana
        $tsvContent = $this->generateBananaTsv($sortedEntries);

        return [
            'entries'     => $sortedEntries,
            'summary'     => $stats,
            'tsv_content' => $tsvContent,
        ];
    }

    /**
     * Trie les fichiers pour traiter les camt.054 (ou contenant ZQR) en premier.
     *
     * @param  array<UploadedFile|string>  $files
     * @return array<UploadedFile|string>
     */
    protected function sortFilesByPriority(array $files): array
    {
        usort($files, function ($a, $b) {
            $nameA = mb_strtoupper($a instanceof UploadedFile ? $a->getClientOriginalName() : basename((string) $a));
            $nameB = mb_strtoupper($b instanceof UploadedFile ? $b->getClientOriginalName() : basename((string) $b));

            $priorityA = (str_contains($nameA, 'CAMT.054') || str_contains($nameA, 'ZQR')) ? 0 : 1;
            $priorityB = (str_contains($nameB, 'CAMT.054') || str_contains($nameB, 'ZQR')) ? 0 : 1;

            return $priorityA <=> $priorityB;
        });

        return $files;
    }

    /**
     * Récupère le contenu brut du fichier avec correction UBS du bug T24:00:00.
     */
    protected function getFileContent(UploadedFile|string $file): string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        if (! $path || ! file_exists($path)) {
            return '';
        }

        $content = file_get_contents($path) ?: '';

        // Correction standard de l'horodatage UBS non conforme XML
        return str_replace(['T24:00:00', '24:00:00'], ['T23:59:59', '23:59:59'], $content);
    }

    /**
     * Analyse un flux XML CAMT (053 ou 054) sans dépendance stricte de version de schéma.
     *
     * @param  array<string, bool>  $processedSignatures
     * @param  array<string, mixed>  $stats
     * @return array<int, array<string, mixed>>
     */
    protected function parseCamtXml(string $xmlContent, array &$processedSignatures, array &$stats): array
    {
        // Chargement SimpleXML sécurisé
        $xml = @simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($xml === false) {
            return [];
        }

        $namespaces = $xml->getNamespaces(true);
        $defaultNamespace = $namespaces[''] ?? null;
        $isCamt053 = str_contains((string) $xml->getName(), 'camt.053') || str_contains($xmlContent, 'camt.053');

        // On enregistre un préfixe générique 'camt' pour simplifier les requêtes XPath
        if ($defaultNamespace) {
            $xml->registerXPathNamespace('camt', $defaultNamespace);
            $ntryNodes = $xml->xpath('//camt:Ntry') ?: [];
        } else {
            $ntryNodes = $xml->xpath('//Ntry') ?: [];
        }

        $parsedEntries = [];

        foreach ($ntryNodes as $ntry) {
            if ($defaultNamespace) {
                $ntry->registerXPathNamespace('camt', $defaultNamespace);
            }

            // Filtrer l'agrégat QR UBS code A89 dans camt.053 pour éviter les doublons avec camt.054
            if ($isCamt053) {
                $proprietaryCode = $this->xpathValue($ntry, 'camt:BkTxCd/camt:Prtry/camt:Cd', $defaultNamespace);
                if ($proprietaryCode === 'A89') {
                    continue;
                }
            }

            $bookingDate = $this->xpathValue($ntry, 'camt:BookgDt/camt:Dt', $defaultNamespace)
                ?: $this->xpathValue($ntry, 'camt:ValDt/camt:Dt', $defaultNamespace)
                ?: date('Y-m-d');

            $ntryAcctRef = $this->xpathValue($ntry, 'camt:AcctSvcrRef', $defaultNamespace) ?: '';
            $txDetailsList = $defaultNamespace ? $ntry->xpath('.//camt:TxDtls') : $ntry->xpath('.//TxDtls');

            if (! empty($txDetailsList)) {
                foreach ($txDetailsList as $txDtls) {
                    if ($defaultNamespace) {
                        $txDtls->registerXPathNamespace('camt', $defaultNamespace);
                    }

                    $txRef = $this->xpathValue($txDtls, 'camt:Refs/camt:AcctSvcrRef', $defaultNamespace)
                        ?: $this->xpathValue($txDtls, 'camt:Refs/camt:EndToEndId', $defaultNamespace)
                        ?: $ntryAcctRef;

                    $item = $this->extractTransactionData($bookingDate, $txDtls, $ntry, $defaultNamespace, $txRef, $processedSignatures, $stats);
                    if ($item !== null) {
                        $parsedEntries[] = $item;
                    }
                }
            } else {
                $item = $this->extractTransactionData($bookingDate, $ntry, $ntry, $defaultNamespace, $ntryAcctRef, $processedSignatures, $stats);
                if ($item !== null) {
                    $parsedEntries[] = $item;
                }
            }
        }

        return $parsedEntries;
    }

    /**
     * Extrait les informations d'une transaction unitaire.
     *
     * @param  array<string, bool>  $processedSignatures
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>|null
     */
    protected function extractTransactionData(
        string $bookingDate,
        SimpleXMLElement $node,
        SimpleXMLElement $fallbackNtry,
        ?string $ns,
        string $refForDedup,
        array &$processedSignatures,
        array &$stats
    ): ?array {
        // Montant
        $amtStr = $this->xpathValue($node, 'camt:Amt', $ns)
            ?: $this->xpathValue($node, 'camt:AmtDtls/camt:TxAmt/camt:Amt', $ns)
            ?: '0.00';
        $amount = abs((float) $amtStr);

        // Sens CRDT / DBIT
        $sens = $this->xpathValue($node, 'camt:CdtDbtInd', $ns)
            ?: $this->xpathValue($fallbackNtry, 'camt:CdtDbtInd', $ns)
            ?: 'CRDT';

        // Référence QR éventuelle
        $qrReference = $this->xpathValue($node, 'camt:RmtInf/camt:Strd/camt:CdtrRefInf/Ref', $ns)
            ?: $this->xpathValue($node, 'camt:RmtInf/camt:Strd/camt:CdtrRefInf/camt:Ref', $ns);

        // Clé de déduplication : date + montant + sens + (ref ou qrRef)
        $uniqueKey = sprintf(
            '%s_%.2f_%s_%s',
            $bookingDate,
            $amount,
            $sens,
            $qrReference ?: ($refForDedup ?: 'NOREF')
        );

        if (isset($processedSignatures[$uniqueKey])) {
            $stats['duplicate_entries']++;

            return null;
        }

        $processedSignatures[$uniqueKey] = true;
        $stats['total_transactions']++;

        // Informations parties prenantes (Débiteur ou Créancier selon sens)
        $partyName = $this->extractPartyName($node, $sens, $ns);

        // Textes descriptifs
        $remittanceParts = [];
        $sources = [
            'camt:RmtInf/camt:Ustrd',
            'camt:RmtInf/camt:Strd/camt:AddtlRmtInf',
            'camt:AddtlTxInf',
            'camt:AddtlNtryInf',
        ];

        foreach ($sources as $source) {
            $val = $this->xpathValue($node, $source, $ns) ?: $this->xpathValue($fallbackNtry, $source, $ns);
            if (! empty($val)) {
                $remittanceParts[] = $val;
            }
        }

        return [
            'raw_date'              => $bookingDate,
            'amount'                => $amount,
            'sens'                  => $sens,
            'qr_reference'          => $qrReference,
            'party_name'            => $partyName,
            'remittance_parts'      => $remittanceParts,
            'transaction_reference' => $refForDedup,
            'unique_key'            => $uniqueKey,
        ];
    }

    /**
     * Extrait le nom de la contrepartie selon le sens de la transaction.
     */
    protected function extractPartyName(SimpleXMLElement $node, string $sens, ?string $ns): ?string
    {
        $prefix = $sens === 'DBIT' ? 'Cdtr' : 'Dbtr';
        $otherPrefix = $sens === 'DBIT' ? 'Dbtr' : 'Cdtr';

        $paths = [
            "camt:RltdPties/camt:{$prefix}/camt:Nm",
            "camt:RltdPties/camt:Ultmt{$prefix}/camt:Nm",
            "camt:RltdPties/camt:{$prefix}/camt:PstlAdr/camt:AdrLine",
            "camt:RltdPties/camt:{$prefix}/camt:PstlAdr/camt:TwnNm",
            "camt:RltdPties/camt:{$otherPrefix}/camt:Nm",
            "camt:RltdPties/camt:Ultmt{$otherPrefix}/camt:Nm",
        ];

        foreach ($paths as $path) {
            $val = $this->xpathValue($node, $path, $ns);
            if (! empty($val)) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Rapproche l'écriture avec la base de données CDN et détermine l'imputation comptable Banana.
     *
     * @param  array<string, mixed>  $entry
     * @param  array<int, array<string, mixed>>  $rules
     * @param  array<string, mixed>  $stats
     * @return array<string, mixed>
     */
    protected function enrichAndReconcileEntry(
        array $entry,
        string $bankAccount,
        string $defaultClientAccount,
        string $defaultSupplierAccount,
        string $allocationMode,
        array $rules,
        bool $reconcileInDatabase,
        array &$stats
    ): array {
        $sens = $entry['sens'];
        $amount = (float) $entry['amount'];
        $qrReference = $entry['qr_reference'];
        $invoice = null;
        $docInvoice = '';
        $vatCode = '';

        // 1. Rapprochement facture QR
        if ($sens === 'CRDT' && ! empty($qrReference)) {
            $invoice = Invoice::query()->where('qr_reference', $qrReference)->first();

            if ($invoice) {
                $docInvoice = (string) $invoice->number;

                if (abs((float) $invoice->total - $amount) > 0.01) {
                    $stats['mismatched_amounts']++;
                    $invoiceStatus = InvoiceStatusEnum::ActionRequired;
                } else {
                    $stats['reconciled_invoices']++;
                    $invoiceStatus = InvoiceStatusEnum::Paid;
                }

                if ($reconcileInDatabase) {
                    $invoice->status = $invoiceStatus;
                    $invoice->paid_on = Carbon::parse($entry['raw_date']);
                    if (empty($invoice->reference) && ! empty($entry['transaction_reference'])) {
                        $invoice->reference = $entry['transaction_reference'];
                    }
                    $invoice->save();
                }
            } else {
                $stats['unmatched_payments']++;
            }
        } elseif ($sens === 'CRDT') {
            $stats['other_transactions']++;
        } else {
            $stats['other_transactions']++;
        }

        // 2. Moteur de rendu de description soigné et enrichi
        $description = $this->buildDescription($entry, $invoice);

        // 3. Détermination des comptes Débit / Crédit et Code TVA pour Banana
        if ($sens === 'CRDT') {
            // L'argent entre en banque : BANQUE AU DÉBIT
            $accountDebit = $bankAccount;
            $accountCredit = $defaultClientAccount;

            if ($invoice) {
                if ($allocationMode === 'provisions') {
                    // Trouver le compte de prestation et le code TVA parmi les positions
                    [$prodAccount, $prodVat] = $this->resolveInvoiceProductAccount($invoice);
                    if ($prodAccount) {
                        $accountCredit = $prodAccount;
                        $vatCode = $prodVat;
                    }
                }
            } else {
                // Règle par mot-clé pour encaissements divers
                [$matchedAccount, $matchedVat] = $this->matchKeywordRule($description, $rules);
                if ($matchedAccount) {
                    $accountCredit = $matchedAccount;
                    $vatCode = $matchedVat;
                }
            }
        } else {
            // L'argent sort de la banque : BANQUE AU CRÉDIT
            $accountCredit = $bankAccount;
            $accountDebit = $defaultSupplierAccount;

            // Règle par mot-clé pour décaissements
            [$matchedAccount, $matchedVat] = $this->matchKeywordRule($description, $rules);
            if ($matchedAccount) {
                $accountDebit = $matchedAccount;
                $vatCode = $matchedVat;
            }
        }

        return [
            'raw_date'           => $entry['raw_date'],
            'date'               => Carbon::parse($entry['raw_date'])->format('Y-m-d'),
            'formatted_date'     => Carbon::parse($entry['raw_date'])->format('d.m.Y'),
            'sens'               => $sens,
            'amount'             => $amount,
            'formatted_amount'   => number_format($amount, 2, '.', "'"),
            'account_debit'      => $accountDebit,
            'account_credit'     => $accountCredit,
            'vat_code'           => $vatCode,
            'doc_invoice'        => $docInvoice,
            'external_reference' => $entry['unique_key'],
            'description'        => $description,
            'qr_reference'       => $qrReference,
            'invoice'            => $invoice,
            'party_name'         => $entry['party_name'],
        ];
    }

    /**
     * Moteur de rendu soigné des descriptions d'écriture.
     * Élimine les mentions superflues et formate une description claire pour Banana.
     *
     * @param  array<string, mixed>  $entry
     */
    protected function buildDescription(array $entry, ?Invoice $invoice): string
    {
        $parts = [];

        // 1. Identification de la partie prenante
        $party = $invoice?->client?->invoicing_name
            ?: $invoice?->client?->name
            ?: $entry['party_name'];

        if (! empty($party)) {
            $parts[] = trim((string) $party);
        }

        // 2. Traitement spécifique des factures QR
        if ($invoice) {
            $parts[] = "Facture F{$invoice->number}";

            // Ajouter le titre ou l'intitulé si distinct du nom de la facture
            if (! empty($invoice->title) && ! str_contains(mb_strtolower($invoice->title), 'facture')) {
                $parts[] = $invoice->title;
            }
        } elseif (! empty($entry['qr_reference'])) {
            // Facture QR non trouvée dans CDN : décoder le numéro de facture contenu dans la référence
            $cleanRef = ltrim($entry['qr_reference'], '0');
            $refNumber = strlen($cleanRef) > 1 ? substr($cleanRef, 0, -1) : $cleanRef;
            $parts[] = "Facture QR F{$refNumber}";
        }

        // 3. Intégration des motifs de paiement / communication
        foreach ($entry['remittance_parts'] as $remittance) {
            $clean = $this->sanitizeText((string) $remittance);
            if (! empty($clean)) {
                $parts[] = $clean;
            }
        }

        if (empty($parts)) {
            return $entry['sens'] === 'CRDT' ? 'Paiement reçu' : 'Paiement émis';
        }

        // 4. Dé-duplication des segments et nettoyage des filtres parasites
        $uniqueParts = [];
        $seen = [];

        foreach ($parts as $part) {
            $normalized = mb_strtolower(preg_replace('/\s+/', '', $part));
            if (! isset($seen[$normalized]) && ! empty($normalized)) {
                $seen[$normalized] = true;
                $uniqueParts[] = $part;
            }
        }

        $fullDescription = implode(' - ', $uniqueParts);

        // Nettoyage final des mentions techniques bancaires suisses
        $patternsToRemove = [
            '/\bCredit QR reference\b/i',
            '/\bCREDIT TRANSACTIONS\b/i',
            '/\bTOTAL ENTRIES\b/i',
            '/\bBVR reference\b/i',
            '/\bNONREF\b/i',
            '/\bNOTPROVIDED\b/i',
            '/\s{2,}/',
        ];

        $cleaned = preg_replace($patternsToRemove, ' ', $fullDescription);
        $cleaned = trim((string) $cleaned, " \t\n\r\0\x0B-");

        // Retirer impérativement tout retour à la ligne ou tabulation pour la compatibilité TSV Banana
        $cleaned = str_replace(["\t", "\r", "\n"], ' ', $cleaned);

        return mb_substr($cleaned, 0, 250);
    }

    /**
     * Recherche le compte et code TVA d'un produit associé aux positions d'une facture.
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function resolveInvoiceProductAccount(Invoice $invoice): array
    {
        $positions = $invoice->positions ?? [];
        if (! is_array($positions)) {
            return [null, null];
        }

        foreach ($positions as $pos) {
            $name = $pos['name'] ?? null;
            if ($name) {
                $product = Product::query()->where('name', $name)->first();
                if ($product && ! empty($product->account_number)) {
                    return [$product->account_number, (string) ($product->vat_code ?? '')];
                }
            }
        }

        return [null, null];
    }

    /**
     * Vérifie si le libellé correspond à une règle par mot-clé configurée.
     *
     * @param  array<int, array<string, mixed>>  $rules
     * @return array{0: ?string, 1: ?string}
     */
    protected function matchKeywordRule(string $description, array $rules): array
    {
        $lowerDesc = mb_strtolower($description);

        foreach ($rules as $rule) {
            $keyword = mb_strtolower(trim((string) ($rule['keyword'] ?? '')));
            if ($keyword !== '' && str_contains($lowerDesc, $keyword)) {
                return [
                    (string) ($rule['account'] ?? ''),
                    (string) ($rule['vat_code'] ?? ''),
                ];
            }
        }

        return [null, null];
    }

    /**
     * Génère la chaîne TSV formatée pour l'import dans Banana Comptabilité Plus.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     */
    public function generateBananaTsv(Collection $entries): string
    {
        // En-têtes officiels Banana (Text file with column headers)
        $headers = [
            'Date',
            'DocInvoice',
            'Description',
            'AccountDebit',
            'AccountCredit',
            'Amount',
            'VatCode',
            'ExternalReference',
        ];

        $lines = [implode("\t", $headers)];

        foreach ($entries as $row) {
            $lines[] = implode("\t", [
                $row['date'],
                $row['doc_invoice'] ?? '',
                str_replace(["\t", "\r", "\n"], ' ', (string) ($row['description'] ?? '')),
                $row['account_debit'] ?? '',
                $row['account_credit'] ?? '',
                number_format((float) $row['amount'], 2, '.', ''),
                $row['vat_code'] ?? '',
                $row['external_reference'] ?? '',
            ]);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Utilitaire XPath pour extraire la valeur texte d'un nœud.
     */
    protected function xpathValue(SimpleXMLElement $node, string $path, ?string $ns): ?string
    {
        if ($ns) {
            $node->registerXPathNamespace('camt', $ns);
            $fullPath = $path;
        } else {
            $fullPath = str_replace('camt:', '', $path);
        }

        $res = $node->xpath($fullPath);
        if ($res && isset($res[0])) {
            $val = trim((string) $res[0]);

            return $val !== '' ? $val : null;
        }

        return null;
    }

    /**
     * Nettoie les chaînes de texte brut bancaire.
     */
    protected function sanitizeText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }
}
