<x-filament-panels::page>
    
<div>
    <ul>
        <li><x-filament::link :href="route('reports.advertisers')" class="underline">Annonceurs</x-filament::link></li>
        <li><x-filament::link :href="route('reports.donors')" class="underline">Donateurs</x-filament::link></li>
        <li><x-filament::link :href="route('reports.interclass-donors')" class="underline">Donateurs interclasses</x-filament::link></li>
        <li><x-filament::link :href="route('reports.client-provisions')" class="underline">Prestations</x-filament::link></li>
        <li><x-filament::link :href="route('reports.journal-provisions')" class="underline">Prestations pour le  journal</x-filament::link></li>
        <li><x-filament::link :href="route('reports.provisions-comparison', [
                            'reference_edition_id'  => \App\Helpers\AppHelper::getCurrentEditionId(),
                            'comparison_edition_id' => \App\Helpers\AppHelper::getCurrentEditionId() - 1,
                            'client_category_id'    => 1,
                        ])" class="underline">Prestations : comparaisons année</x-filament::link></li>
    </ul>
    <p class="text-sm mt-2">Ajouter <code class="text-xs">?export=1</code> à l'URL pour exporter en Excel.</p>
</div>

</x-filament-panels::page>
