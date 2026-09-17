<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilité des dossards – Course de Noël &amp; Trail des Châteaux</title>
    
    {{-- Meta tags SEO --}}
    <meta name="description" content="Consultez en direct le taux de remplissage et la disponibilité des départs pour la Course de Noël et le Trail des Châteaux à Sion.">
    
    @filamentStyles
    @vite(['resources/css/app.css'])

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-full font-sans text-slate-800 {{ $isEmbed ? 'bg-transparent p-2 sm:p-4' : 'bg-gradient-to-b from-sky-50/60 via-slate-50 to-sky-50/40 py-8 px-4 sm:px-6 lg:px-8' }}">

    <div class="{{ $isEmbed ? 'w-full' : 'max-w-5xl mx-auto' }}">

        {{-- En-tête principal (Thème Bleu Neige) --}}
        @if (! $isEmbed)
            <header class="mb-6 text-center sm:text-left sm:flex sm:items-center sm:justify-between border-b border-sky-100 pb-5">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-800 tracking-wide mb-2">
                        <svg class="w-2 h-2 fill-sky-600 animate-pulse" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3"/></svg>
                        Inscriptions en direct
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        {{ $event['title'] ?? 'Course de Noël et Trail des Châteaux' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $event['date'] ?? '12 décembre 2026' }} • {{ $event['location'] ?? 'Sion, Valais' }} • Taux de remplissage officiel
                    </p>
                </div>

                <div class="mt-4 sm:mt-0 flex items-center justify-center sm:justify-end">
                    <a href="{{ $event['registration_url'] }}" target="_blank" rel="noopener noreferrer" 
                       class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 shadow-sm hover:shadow transition-all duration-150">
                        Accéder aux inscriptions
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                </div>
            </header>
        @else
            <div class="mb-3 flex items-center justify-between border-b border-sky-100 pb-2.5">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Disponibilité des courses</h2>
                    <p class="text-xs text-slate-500">Mise à jour en temps réel (fuseau suisse)</p>
                </div>
                <a href="{{ $event['registration_url'] }}" target="_blank" rel="noopener noreferrer" 
                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-700 px-3 py-1.5 rounded-lg shadow-xs transition-colors">
                    S'inscrire
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @endif

        {{-- Bandeau info indicatif & Horodatage suisse --}}
        <div class="mb-5 p-3 rounded-xl bg-white/90 backdrop-blur-sm border border-sky-100 shadow-xs flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2">
                <div class="w-2.5 h-2.5 rounded-full bg-sky-500 ring-4 ring-sky-100"></div>
                <span class="text-xs text-slate-600 font-medium">
                    Actualisé le <strong class="text-slate-900">{{ $lastUpdatedAt }}</strong>
                    @if ($isFallback)
                        <span class="text-amber-600">(données en cache)</span>
                    @endif
                </span>
            </div>

            {{-- Légende épurée --}}
            <div class="flex items-center gap-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-sky-500"></span> Dispo</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span> &gt; 70%</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-500"></span> &gt; 90%</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-500"></span> Complet</span>
            </div>
        </div>

        {{-- Grille des courses épurée et ultra-lisible --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
            @forelse ($courses as $course)
                <div class="bg-white rounded-2xl p-4.5 border border-sky-100/80 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between {{ $course['is_closed'] ? 'bg-slate-50/70 border-slate-200' : '' }}">
                    
                    <div>
                        {{-- Ligne 1 : Nom (adouci et fluide sur 2 lignes) et Pourcentage/Badge --}}
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <h3 class="font-medium sm:font-semibold text-slate-700 text-sm sm:text-[14.5px] leading-snug {{ $course['is_closed'] ? 'text-slate-500' : '' }}">
                                {{ $course['name'] }}
                            </h3>

                            <div class="shrink-0 flex items-center gap-1.5 pt-0.5">
                                @if ($course['is_closed'])
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        Complet
                                    </span>
                                @elseif ($course['has_limit'] && $course['percentage'] !== null)
                                    @php
                                        $pillClasses = match($course['color_theme']) {
                                            'rose' => 'bg-rose-50 text-rose-700 border-rose-200 font-bold',
                                            'amber' => 'bg-amber-50 text-amber-700 border-amber-200 font-semibold',
                                            default => 'bg-sky-50 text-sky-700 border-sky-200 font-semibold',
                                        };
                                    @endphp
                                    <span class="px-2.5 py-0.5 rounded-full text-xs border tabular-nums {{ $pillClasses }}">
                                        {{ $course['percentage'] }}%
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700 border border-sky-200">
                                        Ouvert
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Ligne 2 : Jauge visuelle plus épaisse et fluide --}}
                        <div class="my-2">
                            @if ($course['has_limit'] && $course['percentage'] !== null)
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200/60 shadow-inner">
                                    @php
                                        $barColor = match($course['color_theme']) {
                                            'slate' => 'bg-slate-500',
                                            'rose' => 'bg-gradient-to-r from-amber-400 to-rose-500',
                                            'amber' => 'bg-gradient-to-r from-sky-400 to-amber-500',
                                            default => 'bg-gradient-to-r from-sky-400 to-blue-500',
                                        };
                                    @endphp
                                    <div class="h-full rounded-full transition-all duration-700 ease-out {{ $barColor }}"
                                         style="width: {{ max(4, min(100, $course['percentage'])) }}%"></div>
                                </div>
                            @else
                                <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200/60 shadow-inner">
                                    <div class="h-full rounded-full bg-sky-400 w-1/4"></div>
                                </div>
                            @endif
                        </div>

                        {{-- Détail par degré scolaire si disponible (Interclasses 3H à 8H) --}}
                        @if (! empty($course['levels']))
                            <div class="mt-3 pt-2.5 border-t border-sky-100/70">
                                <div class="grid grid-cols-3 gap-1.5">
                                    @foreach ($course['levels'] as $lvl)
                                        <div class="bg-sky-50/50 rounded-lg p-1.5 border border-sky-100/80 flex flex-col justify-between">
                                            <div class="flex items-center justify-between text-[11px] mb-1 font-semibold">
                                                <span class="text-slate-800 font-bold">{{ $lvl['level'] }}</span>
                                                <span class="tabular-nums {{ $lvl['color_theme'] === 'slate' ? 'text-slate-600' : ($lvl['color_theme'] === 'rose' ? 'text-rose-600' : ($lvl['color_theme'] === 'amber' ? 'text-amber-600' : 'text-sky-700')) }}">
                                                    {{ $lvl['percentage'] }}%
                                                </span>
                                            </div>
                                            <div class="w-full bg-slate-200/70 rounded-full h-1.5 overflow-hidden">
                                                @php
                                                    $lvlBarColor = match($lvl['color_theme']) {
                                                        'slate' => 'bg-slate-500',
                                                        'rose' => 'bg-gradient-to-r from-amber-400 to-rose-500',
                                                        'amber' => 'bg-gradient-to-r from-sky-400 to-amber-500',
                                                        default => 'bg-gradient-to-r from-sky-400 to-blue-500',
                                                    };
                                                @endphp
                                                <div class="h-full rounded-full {{ $lvlBarColor }}" style="width: {{ max(4, min(100, $lvl['percentage'])) }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Ligne 3 (Optionnelle) : Uniquement si date limite présente --}}
                    @if (! empty($course['deadline_label']))
                        <div class="pt-2 border-t border-slate-100/80 flex items-center justify-end text-[11px] text-slate-400 mt-2">
                            <span class="inline-flex items-center gap-1 font-medium">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                Délai : {{ $course['deadline_label'] }}
                            </span>
                        </div>
                    @endif
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl p-8 text-center text-slate-500 border border-slate-200">
                    Aucune course n'est disponible actuellement.
                </div>
            @endforelse
        </div>

        {{-- Pied de page --}}
        @if (! $isEmbed)
            <footer class="mt-10 pt-5 border-t border-sky-100 text-center text-xs text-slate-500">
                <p>Course de Noël et Trail des Châteaux</p>
                <p class="mt-1 text-slate-400">Données synchronisées automatiquement toutes les 15 minutes.</p>
            </footer>
        @endif

    </div>

    {{-- Script pour auto-adapter la hauteur en iframe si embed --}}
    @if ($isEmbed)
        <script>
            function notifyParentHeight() {
                const height = document.documentElement.scrollHeight;
                window.parent.postMessage({ type: 'cdn-widget-resize', height: height }, '*');
            }
            window.addEventListener('load', notifyParentHeight);
            window.addEventListener('resize', notifyParentHeight);
        </script>
    @endif

    @vite('resources/js/app.js')
</body>
</html>
