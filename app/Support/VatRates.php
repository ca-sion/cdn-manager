<?php

namespace App\Support;

/**
 * Taux de TVA proposés dans les formulaires (Produit, Facture, Prestation) : une seule définition.
 * La clé est la valeur enregistrée et utilisée pour les calculs ; le texte est ce que l'utilisateur voit.
 */
class VatRates
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            '8.1' => '8.1',
            '3.8' => '3.8',
            '2.6' => '2.6',
            '0'   => '0 (exonéré)',
        ];
    }
}
