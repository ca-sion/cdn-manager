<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Edition;
use App\Models\Invoice;
use App\Models\Product;
use App\Enums\InvoiceStatusEnum;
use App\Services\BananaCamtService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('parses camt xml, deduplicates, and generates banana tsv correctly', function () {
    $service = new BananaCamtService;

    // XML camt.054 simulé avec un encaissement QR
    $camt054Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.054.001.04">
    <BkToCstmrDbtCdtNtfctn>
        <Ntry>
            <Amt Ccy="CHF">150.00</Amt>
            <CdtDbtInd>CRDT</CdtDbtInd>
            <BookgDt><Dt>2026-10-14</Dt></BookgDt>
            <AcctSvcrRef>REF-054-001</AcctSvcrRef>
            <NtryDtls>
                <TxDtls>
                    <Refs><AcctSvcrRef>REF-TX-001</AcctSvcrRef></Refs>
                    <Amt Ccy="CHF">150.00</Amt>
                    <CdtDbtInd>CRDT</CdtDbtInd>
                    <RltdPties>
                        <Dbtr><Nm>Entreprise ABC SA</Nm></Dbtr>
                    </RltdPties>
                    <RmtInf>
                        <Strd>
                            <CdtrRefInf><Ref>000000000000000000000001235</Ref></CdtrRefInf>
                        </Strd>
                        <Ustrd>Paiement facture sponsoring</Ustrd>
                    </RmtInf>
                </TxDtls>
            </NtryDtls>
        </Ntry>
    </BkToCstmrDbtCdtNtfctn>
</Document>
XML;

    // XML camt.053 simulé avec l'agrégat A89 (qui doit être ignoré) et un décaissement
    $camt053Xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.04">
    <BkToCstmrStmt>
        <!-- Agrégat A89 UBS à ignorer -->
        <Ntry>
            <Amt Ccy="CHF">150.00</Amt>
            <CdtDbtInd>CRDT</CdtDbtInd>
            <BkTxCd><Prtry><Cd>A89</Cd></Prtry></BkTxCd>
            <BookgDt><Dt>2026-10-14</Dt></BookgDt>
            <AcctSvcrRef>AGREGAT-A89</AcctSvcrRef>
        </Ntry>
        <!-- Décaissement réel (frais UBS ou fournisseur) -->
        <Ntry>
            <Amt Ccy="CHF">45.00</Amt>
            <CdtDbtInd>DBIT</CdtDbtInd>
            <BookgDt><Dt>2026-10-14</Dt></BookgDt>
            <AcctSvcrRef>REF-053-DEBIT</AcctSvcrRef>
            <NtryDtls>
                <TxDtls>
                    <Amt Ccy="CHF">45.00</Amt>
                    <CdtDbtInd>DBIT</CdtDbtInd>
                    <RltdPties>
                        <Cdtr><Nm>UBS Switzerland AG</Nm></Cdtr>
                    </RltdPties>
                    <RmtInf>
                        <Ustrd>Frais de gestion de compte</Ustrd>
                    </RmtInf>
                </TxDtls>
            </NtryDtls>
        </Ntry>
    </BkToCstmrStmt>
</Document>
XML;

    $temp054 = tempnam(sys_get_temp_dir(), 'camt054_').'CAMT.054.xml';
    $temp053 = tempnam(sys_get_temp_dir(), 'camt053_').'CAMT.053.xml';
    file_put_contents($temp054, $camt054Xml);
    file_put_contents($temp053, $camt053Xml);

    $result = $service->processFiles([$temp053, $temp054], [
        'bank_account'     => '1020',
        'client_account'   => '1100',
        'supplier_account' => '2000',
        'reconcile'        => false,
        'rules'            => [
            ['keyword' => 'UBS', 'account' => '6900', 'vat_code' => ''],
        ],
    ]);

    @unlink($temp054);
    @unlink($temp053);

    // Vérification des stats
    expect($result['summary']['total_transactions'])->toBe(2)
        ->and($result['summary']['total_credit'])->toBe(150.0)
        ->and($result['summary']['total_debit'])->toBe(45.0);

    // Vérification des écritures
    $entries = $result['entries'];
    expect($entries)->toHaveCount(2);

    // 1ère écriture : CRDT -> Débit 1020, Crédit 1100
    $creditEntry = $entries->firstWhere('sens', 'CRDT');
    expect($creditEntry['account_debit'])->toBe('1020')
        ->and($creditEntry['account_credit'])->toBe('1100')
        ->and($creditEntry['amount'])->toBe(150.0)
        ->and($creditEntry['description'])->toContain('Entreprise ABC SA')
        ->and($creditEntry['description'])->toContain('Facture QR F123');

    // 2ème écriture : DBIT -> Débit 6900 (règle UBS), Crédit 1020
    $debitEntry = $entries->firstWhere('sens', 'DBIT');
    expect($debitEntry['account_debit'])->toBe('6900')
        ->and($debitEntry['account_credit'])->toBe('1020')
        ->and($debitEntry['amount'])->toBe(45.0)
        ->and($debitEntry['description'])->toContain('UBS Switzerland AG');

    // Vérification du fichier TSV
    expect($result['tsv_content'])->toContain("Date\tDocInvoice\tDescription\tAccountDebit\tAccountCredit\tAmount\tVatCode\tExternalReference")
        ->and($result['tsv_content'])->toContain("2026-10-14\t\tEntreprise ABC SA - Facture QR F123 - Paiement facture sponsoring\t1020\t1100\t150.00\t\t")
        ->and($result['tsv_content'])->toContain("2026-10-14\t\tUBS Switzerland AG - Frais de gestion de compte\t6900\t1020\t45.00\t\t");
});

it('reconciles invoice in database and uses product account in provisions mode', function () {
    $service = new BananaCamtService;

    $edition = Edition::create([
        'name' => '2026',
        'year' => 2026,
    ]);

    $client = Client::create([
        'name'           => 'Sponsor Principal SA',
        'invoicing_name' => 'Sponsor Principal SA',
    ]);

    // Créer un produit avec un numéro de compte et code TVA
    $product = Product::create([
        'name'           => 'Banderole 2026',
        'edition_id'     => $edition->id,
        'cost'           => 500.0,
        'tax_rate'       => 8.1,
        'account_number' => '3200',
        'vat_code'       => 'USt81',
    ]);

    // Créer une facture avec cette position
    $qrRef = '210000000000000000000009999';
    $invoice = new Invoice;
    $invoice->edition_id = $edition->id;
    $invoice->client_id = $client->id;
    $invoice->number = '2026099';
    $invoice->title = 'Partenariat Banderoles';
    $invoice->status = 'ready';
    $invoice->qr_reference = $qrRef;
    $invoice->positions = [
        [
            'name'        => 'Banderole 2026',
            'cost'        => 500.0,
            'quantity'    => 1,
            'tax_rate'    => 8.1,
            'include_vat' => true,
        ],
    ];
    $invoice->save();

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.054.001.04">
    <BkToCstmrDbtCdtNtfctn>
        <Ntry>
            <Amt Ccy="CHF">500.00</Amt>
            <CdtDbtInd>CRDT</CdtDbtInd>
            <BookgDt><Dt>2026-10-14</Dt></BookgDt>
            <NtryDtls>
                <TxDtls>
                    <Amt Ccy="CHF">500.00</Amt>
                    <CdtDbtInd>CRDT</CdtDbtInd>
                    <RltdPties>
                        <Dbtr><Nm>Sponsor Principal SA</Nm></Dbtr>
                    </RltdPties>
                    <RmtInf>
                        <Strd>
                            <CdtrRefInf><Ref>{$qrRef}</Ref></CdtrRefInf>
                        </Strd>
                    </RmtInf>
                </TxDtls>
            </NtryDtls>
        </Ntry>
    </BkToCstmrDbtCdtNtfctn>
</Document>
XML;

    $tempFile = tempnam(sys_get_temp_dir(), 'camt_reconcile_').'.xml';
    file_put_contents($tempFile, $xml);

    $result = $service->processFiles([$tempFile], [
        'bank_account'    => '1020',
        'allocation_mode' => 'provisions',
        'reconcile'       => true,
    ]);

    @unlink($tempFile);

    expect($result['summary']['reconciled_invoices'])->toBe(1);

    // Vérifier l'écriture générée
    $entry = $result['entries']->first();
    expect($entry['doc_invoice'])->toBe('2026099')
        ->and($entry['account_debit'])->toBe('1020')
        ->and($entry['account_credit'])->toBe('3200')
        ->and($entry['vat_code'])->toBe('USt81')
        ->and($entry['description'])->toContain('Sponsor Principal SA')
        ->and($entry['description'])->toContain('Facture F2026099')
        ->and($entry['description'])->toContain('Partenariat Banderoles');

    // Vérifier que la facture est bien passée en 'paid' dans la BD
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatusEnum::Paid)
        ->and($invoice->paid_on->toDateString())->toBe('2026-10-14');
});
