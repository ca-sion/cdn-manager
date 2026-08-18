<x-filament-panels::page>
    
<div>
    <h3 class="font-bold text-lg mb-2">Rapports & Exports de l'édition</h3>
    <ul class="space-y-1">
        <li><x-filament::link :href="route('reports.financial')" class="underline">Rapport financier</x-filament::link></li>
        <li><x-filament::link :href="route('reports.invoices')" class="underline">Factures émises</x-filament::link></li>
        <li><x-filament::link :href="route('reports.advertisers')" class="underline">Annonceurs</x-filament::link></li>
        <li><x-filament::link :href="route('reports.donors')" class="underline">Donateurs</x-filament::link></li>
        <li><x-filament::link :href="route('reports.vip')" class="underline">VIP</x-filament::link></li>
        <li><x-filament::link :href="route('reports.banners')" class="underline">Banderoles</x-filament::link></li>
        <li><x-filament::link :href="route('reports.screens')" class="underline">Écrans</x-filament::link></li>
        <li><x-filament::link :href="route('reports.interclass-donors')" class="underline">Donateurs interclasses</x-filament::link></li>
        <li><x-filament::link :href="route('reports.client-provisions')" class="underline">Prestations (détail)</x-filament::link></li>
        <li><x-filament::link :href="route('reports.client-provisions-matrix')" class="underline">Prestations (matrice)</x-filament::link></li>
        <li><x-filament::link :href="route('reports.journal-provisions')" class="underline">Prestations pour le journal</x-filament::link></li>
        <li><x-filament::link :href="route('reports.provisions-comparison', [
                            'reference_edition_id'  => \App\Helpers\AppHelper::getCurrentEditionId(),
                            'comparison_edition_id' => \App\Helpers\AppHelper::getCurrentEditionId() - 1,
                            'client_category_id'    => 1,
                        ])" class="underline">Prestations : comparaisons année</x-filament::link></li>
        <li><x-filament::link :href="route('reports.elites')" class="underline">Coureurs Élite</x-filament::link></li>
    </ul>
    <p class="text-sm mt-3 text-gray-600 dark:text-gray-400">Astuce : Ajoutez <code class="text-xs font-mono bg-gray-100 dark:bg-gray-800 px-1 py-0.5 rounded">?export=1</code> à l'URL pour télécharger les rapports au format Excel.</p>

    <h3 class="font-bold text-lg mt-6 mb-2">Interfaces de gestion</h3>
    <ul class="space-y-1">
        <li><x-filament::link :href="route('front.elite-manager')" target="_blank" class="underline">Gestion des coureurs Élite</x-filament::link></li>
        <li><x-filament::link :href="route('front.run-registration.manager')" target="_blank" class="underline">Gestion des dossiers d'inscription</x-filament::link></li>
        <li><x-filament::link :href="\App\Filament\Pages\ProFormaInvoice::getUrl()" class="underline">Générer un justificatif / Pro forma</x-filament::link></li>
    </ul>
</div>

</x-filament-panels::page>
