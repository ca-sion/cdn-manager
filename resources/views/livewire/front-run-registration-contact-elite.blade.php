@php
    $email = setting('elite_manager_email', 'elites@coursedenoel.ch');
@endphp

<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-3">
        <span class="text-2xl">📩</span>
        <div>
            <div class="font-bold text-sm text-gray-900 dark:text-white">Responsable des Courses Élite</div>
            <div class="text-xs text-gray-500 font-mono">{{ $email }}</div>
        </div>
    </div>
    <a href="mailto:{{ $email }}?subject=Question%20Dossier%20Elite" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium text-xs rounded-lg shadow-sm transition">
        ✉️ Contacter le responsable
    </a>
</div>
