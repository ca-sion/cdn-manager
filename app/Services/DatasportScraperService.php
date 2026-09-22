<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Run;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\RunRegistrationElement;

class DatasportScraperService
{
    public const CACHE_KEY = 'datasport_fill_rates_data';

    public const BACKUP_CACHE_KEY = 'datasport_fill_rates_backup';

    /**
     * Seuil de dossards restants à partir duquel la course est considérée comme complète (100%).
     */
    public const FULL_THRESHOLD_SLOTS = 15;

    /**
     * Récupère le taux de remplissage de toutes les courses avec gestion du cache et fallback.
     *
     * @return array{
     *     event: array{title: string, date: string, location: string, registration_url: string},
     *     courses: array<int, array{
     *         name: string,
     *         slug: string,
     *         has_limit: bool,
     *         percentage: ?int,
     *         status: string,
     *         badge_label: string,
     *         status_label: string,
     *         color_theme: string,
     *         is_closed: bool,
     *         deadline_label: ?string,
     *         registration_url: string
     *     }>,
     *     last_updated_at: string,
     *     is_fallback: bool
     * }
     */
    public function getFillRates(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && Cache::has(self::CACHE_KEY)) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && ! empty($cached['courses'])) {
                return $cached;
            }
        }

        // Protection anti-concurrence (Cache Stampede)
        $lock = Cache::lock('datasport_scrape_lock', 10);
        $acquired = $forceRefresh || $lock->get();

        if (! $acquired) {
            // Un autre processus est déjà en train de rafraîchir : on sert immédiatement le backup
            if (Cache::has(self::BACKUP_CACHE_KEY)) {
                $backup = Cache::get(self::BACKUP_CACHE_KEY);
                if (is_array($backup)) {
                    return $backup;
                }
            }
        }

        try {
            $freshData = $this->scrapeAndProcess();

            $ttlMinutes = (int) config('datasport.cache_ttl_minutes', 15);
            Cache::put(self::CACHE_KEY, $freshData, now()->addMinutes($ttlMinutes));
            Cache::put(self::BACKUP_CACHE_KEY, $freshData, now()->addDays(30));

            return $freshData;
        } catch (\Throwable $e) {
            Log::warning('Datasport scraping failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            if (Cache::has(self::BACKUP_CACHE_KEY)) {
                $backup = Cache::get(self::BACKUP_CACHE_KEY);
                if (is_array($backup)) {
                    $backup['is_fallback'] = true;

                    return $backup;
                }
            }

            return $this->buildFallbackFromDatabase();
        } finally {
            if ($acquired && ! $forceRefresh) {
                optional($lock)->release();
            }
        }
    }

    /**
     * Exécute le scraping Datasport et traite les courses avec agrégation interne CDN Manager.
     */
    public function scrapeAndProcess(): array
    {
        $url = (string) config('datasport.startlist_url');

        $response = Http::timeout(7)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; CdnManager/1.0; +https://coursedenoel.ch)',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP error from Datasport: '.$response->status());
        }

        $html = $response->body();

        if (! preg_match('/<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s', $html, $matches)) {
            throw new \RuntimeException('Unable to locate __NEXT_DATA__ script block in Datasport response.');
        }

        $jsonData = json_decode($matches[1], true);
        if (! is_array($jsonData)) {
            throw new \RuntimeException('Failed to decode __NEXT_DATA__ JSON payload.');
        }

        $pageResponse = $jsonData['props']['pageProps']['response'] ?? [];
        $header = $pageResponse['header'] ?? [];
        $contests = $pageResponse['contests'] ?? [];
        $filterValues = $pageResponse['filter'][0]['values'] ?? [];

        // Dictionnaire des inscrits Datasport par nom de contest (addition H + F)
        $enteredByName = [];
        foreach ($contests as $contest) {
            $name = trim((string) ($contest['name'] ?? ''));
            $totalEntered = 0;

            foreach ($contest['lists'] ?? [] as $list) {
                if (($list['type'] ?? '') === 'gender') {
                    $totalEntered += (int) ($list['count.entered'] ?? 0);
                }
            }

            if ($name !== '') {
                $enteredByName[$name] = $totalEntered;
                $enteredByName[Str::slug($name)] = $totalEntered;
            }
        }

        // Chargement sécurisé des Runs avec le comptage des inscrits internes (RunRegistrationElement)
        try {
            $dbRuns = Run::withCount('runRegistrationElements')->get();
        } catch (\Throwable $e) {
            Log::info('Unable to load runs from database: '.$e->getMessage());
            $dbRuns = collect();
        }

        $courses = [];
        $defaultRegistrationUrl = (string) config('datasport.registration_url');

        $seenSlugs = [];

        foreach ($filterValues as $filter) {
            $rawName = trim((string) ($filter['name'] ?? ''));
            $slug = (string) ($filter['target']['slug'] ?? Str::slug($rawName));

            // Ignorer l'entrée "Toutes les épreuves"
            if ($rawName === '' || $slug === '' || str_contains(mb_strtolower($rawName), 'toutes les')) {
                continue;
            }

            $seenSlugs[$slug] = true;

            // Inscrits Datasport (H + F)
            $datasportCount = $enteredByName[$rawName] ?? ($enteredByName[$slug] ?? 0);

            // Recherche du Run correspondant en BD et intégration des inscrits internes CDN
            $matchedRun = $this->findMatchingRun($rawName, $slug, $dbRuns);
            $internalCount = $this->getInternalRegistrationsCount($matchedRun);
            $totalEntered = $datasportCount + $internalCount;

            $limit = $this->resolveLimitForCourse($rawName, $slug, $matchedRun);
            $deadlineLabel = $this->resolveDeadlineLabel($matchedRun);

            $courses[] = $this->formatCourseItem(
                name: $rawName,
                slug: $slug,
                totalEntered: $totalEntered,
                limit: $limit,
                deadlineLabel: $deadlineLabel,
                defaultRegistrationUrl: $defaultRegistrationUrl
            );
        }

        // Ajouter d'éventuelles courses présentes dans `contests` mais non listées dans `filters`
        foreach ($contests as $contest) {
            $rawName = trim((string) ($contest['name'] ?? ''));
            $slug = Str::slug($rawName);

            if ($rawName === '' || isset($seenSlugs[$slug])) {
                continue;
            }

            $seenSlugs[$slug] = true;
            $datasportCount = $enteredByName[$rawName] ?? 0;

            $matchedRun = $this->findMatchingRun($rawName, $slug, $dbRuns);
            $internalCount = $this->getInternalRegistrationsCount($matchedRun);
            $totalEntered = $datasportCount + $internalCount;

            $limit = $this->resolveLimitForCourse($rawName, $slug, $matchedRun);
            $deadlineLabel = $this->resolveDeadlineLabel($matchedRun);

            $courses[] = $this->formatCourseItem(
                name: $rawName,
                slug: $slug,
                totalEntered: $totalEntered,
                limit: $limit,
                deadlineLabel: $deadlineLabel,
                defaultRegistrationUrl: $defaultRegistrationUrl
            );
        }

        return [
            'event' => [
                'title'            => (string) ($header['title'] ?? 'Course de Noël et Trail des Châteaux, Sion'),
                'date'             => (string) ($header['date'] ?? '12.12.2026'),
                'location'         => (string) ($header['location'] ?? 'Sion, Valais'),
                'registration_url' => (string) ($header['onreg']['link'] ?? $defaultRegistrationUrl),
            ],
            'courses'         => $courses,
            'last_updated_at' => now('Europe/Zurich')->translatedFormat('d F Y à H:i'),
            'is_fallback'     => false,
        ];
    }

    /**
     * Recherche le modèle Run correspondant à une épreuve.
     */
    protected function findMatchingRun(string $name, string $slug, Collection $dbRuns): ?Run
    {
        $datasportCode = config("datasport.slug_mappings.{$slug}");

        return $dbRuns->first(function (Run $run) use ($name, $slug, $datasportCode) {
            if ($datasportCode && $run->datasport_code === $datasportCode) {
                return true;
            }
            if (mb_strtolower($run->name) === mb_strtolower($name)) {
                return true;
            }
            if (Str::slug($run->name) === $slug) {
                return true;
            }
            if (str_starts_with(mb_strtolower($name), mb_strtolower($run->name)) || str_starts_with(mb_strtolower($run->name), mb_strtolower($name))) {
                return true;
            }

            return false;
        });
    }

    /**
     * Calcule le total d'inscriptions internes saisies sur CDN Manager
     * (formulaires entreprises, interclasses, écoles, élites, etc.).
     */
    protected function getInternalRegistrationsCount(?Run $run): int
    {
        if (! $run) {
            return 0;
        }

        $elementsCount = (int) ($run->run_registration_elements_count ?? 0);
        $registrationsNumber = (int) ($run->registrations_number ?? 0);

        return max($elementsCount, $registrationsNumber);
    }

    /**
     * Résout la date limite d'inscription formatée si présente sur le Run.
     */
    protected function resolveDeadlineLabel(?Run $run): ?string
    {
        if ($run && $run->registrations_deadline) {
            return $run->registrations_deadline->translatedFormat('d F Y');
        }

        return null;
    }

    /**
     * Résout la limite maximale pour une épreuve (Priorité : BD `Run` > Config > Null).
     */
    protected function resolveLimitForCourse(string $name, string $slug, ?Run $matchedRun): ?int
    {
        if ($matchedRun && $matchedRun->registrations_limit && $matchedRun->registrations_limit > 0) {
            return (int) $matchedRun->registrations_limit;
        }

        // 2. Recherche dans config/datasport.php
        $configLimit = config("datasport.default_limits.{$slug}") ?? config("datasport.default_limits.{$name}");
        if ($configLimit !== null && (int) $configLimit > 0) {
            return (int) $configLimit;
        }

        return null;
    }

    /**
     * Formate un item de course avec calcul des jauges et statuts.
     * Précision à 15 dossards près (si reste <= 15 places => 100% / Complet).
     */
    protected function formatCourseItem(
        string $name,
        string $slug,
        int $totalEntered,
        ?int $limit,
        ?string $deadlineLabel,
        string $defaultRegistrationUrl
    ): array {
        $hasLimit = $limit !== null && $limit > 0;
        $percentage = null;
        $status = 'open';
        $badgeLabel = 'Inscriptions ouvertes';
        $statusLabel = 'Places disponibles';
        $colorTheme = 'sky';
        $isClosed = false;

        if ($hasLimit) {
            $remaining = $limit - $totalEntered;

            // Règle des 15 dossards près : si <= 15 dossards restants ou dépassé -> 100% complet
            if ($remaining <= self::FULL_THRESHOLD_SLOTS || $totalEntered >= $limit) {
                $percentage = 100;
                $status = 'full';
                $badgeLabel = 'Complet';
                $statusLabel = 'Complet';
                $colorTheme = 'slate';
                $isClosed = true;
            } else {
                // Pourcentage entier arrondi sans décimales bizarres
                $percentage = (int) round(($totalEntered / $limit) * 100);
                $percentage = min(99, max(0, $percentage));

                if ($percentage >= 90) {
                    $status = 'almost_full';
                    $badgeLabel = 'Derniers dossards';
                    $statusLabel = 'Presque complet';
                    $colorTheme = 'rose';
                } elseif ($percentage >= 70) {
                    $status = 'high';
                    $badgeLabel = 'Remplissage élevé';
                    $statusLabel = 'Plus que quelques places';
                    $colorTheme = 'amber';
                } elseif ($percentage > 0) {
                    $status = 'available';
                    $badgeLabel = 'Places disponibles';
                    $statusLabel = 'Inscriptions ouvertes';
                    $colorTheme = 'sky';
                } else {
                    $status = 'open';
                    $badgeLabel = 'Places disponibles';
                    $statusLabel = 'Inscriptions ouvertes';
                    $colorTheme = 'sky';
                }
            }
        }

        $isInterclasses = $slug === 'interclasses' || str_contains(mb_strtolower($name), 'interclasses');
        $levels = $isInterclasses ? $this->getInterclassesLevelsFillRate() : null;

        return [
            'name'             => $name,
            'slug'             => $slug,
            'has_limit'        => $hasLimit,
            'percentage'       => $percentage,
            'status'           => $status,
            'badge_label'      => $badgeLabel,
            'status_label'     => $statusLabel,
            'color_theme'      => $colorTheme,
            'is_closed'        => $isClosed,
            'deadline_label'   => $deadlineLabel,
            'levels'           => $levels,
            'registration_url' => $defaultRegistrationUrl,
        ];
    }

    /**
     * Calcule le taux de remplissage par niveau scolaire pour les Interclasses (3H à 8H).
     *
     * @return array<int, array{
     *     level: string,
     *     percentage: int,
     *     status: string,
     *     badge_label: string,
     *     color_theme: string,
     *     is_closed: bool
     * }>
     */
    public function getInterclassesLevelsFillRate(): array
    {
        $limitPerLevel = (int) config('datasport.interclasses_limit_per_level', 200);
        $countsByLevel = [];

        try {
            $countsByLevel = RunRegistrationElement::whereHas('runRegistration', function ($q) {
                $q->where('run_registration_type', 'school');
            })
                ->join('run_registrations', 'run_registrations.id', '=', 'run_registration_elements.run_registration_id')
                ->whereNull('run_registrations.deleted_at')
                ->whereNull('run_registration_elements.deleted_at')
                ->groupBy('run_registrations.school_class_level')
                ->selectRaw('run_registrations.school_class_level, count(*) as total')
                ->pluck('total', 'school_class_level')
                ->toArray();
        } catch (\Throwable $e) {
            Log::info('Unable to query interclasses level counts: '.$e->getMessage());
        }

        $levels = ['3H', '4H', '5H', '6H', '7H', '8H'];
        $result = [];

        foreach ($levels as $lvl) {
            $count = (int) ($countsByLevel[$lvl] ?? 0);
            $remaining = $limitPerLevel - $count;

            if ($remaining <= self::FULL_THRESHOLD_SLOTS || $count >= $limitPerLevel) {
                $percentage = 100;
                $status = 'full';
                $badgeLabel = 'Complet';
                $colorTheme = 'slate';
                $isClosed = true;
            } else {
                $percentage = (int) round(($count / $limitPerLevel) * 100);
                $percentage = min(99, max(0, $percentage));

                if ($percentage >= 90) {
                    $status = 'almost_full';
                    $badgeLabel = 'Derniers dossards';
                    $colorTheme = 'rose';
                } elseif ($percentage >= 70) {
                    $status = 'high';
                    $badgeLabel = 'Remplissage élevé';
                    $colorTheme = 'amber';
                } elseif ($percentage > 0) {
                    $status = 'available';
                    $badgeLabel = 'Places disponibles';
                    $colorTheme = 'sky';
                } else {
                    $status = 'open';
                    $badgeLabel = 'Places disponibles';
                    $colorTheme = 'sky';
                }
                $isClosed = false;
            }

            $result[] = [
                'level'       => $lvl,
                'percentage'  => $percentage,
                'status'      => $status,
                'badge_label' => $badgeLabel,
                'color_theme' => $colorTheme,
                'is_closed'   => $isClosed,
            ];
        }

        return $result;
    }

    /**
     * Génère un jeu de données de fallback depuis la table `Run` si Datasport est inaccessible
     * et qu'aucun cache n'existe encore.
     */
    protected function buildFallbackFromDatabase(): array
    {
        try {
            $dbRuns = Run::withCount('runRegistrationElements')->get();
        } catch (\Throwable) {
            $dbRuns = collect();
        }

        $courses = [];
        $defaultRegistrationUrl = (string) config('datasport.registration_url');

        foreach ($dbRuns as $run) {
            $slug = Str::slug($run->name);
            $limit = $run->registrations_limit ?: config("datasport.default_limits.{$slug}");
            $internalCount = $this->getInternalRegistrationsCount($run);
            $deadlineLabel = $this->resolveDeadlineLabel($run);

            $courses[] = $this->formatCourseItem(
                name: $run->name,
                slug: $slug,
                totalEntered: $internalCount,
                limit: $limit,
                deadlineLabel: $deadlineLabel,
                defaultRegistrationUrl: $defaultRegistrationUrl
            );
        }

        return [
            'event' => [
                'title'            => 'Course de Noël et Trail des Châteaux, Sion',
                'date'             => '12.12.2026',
                'location'         => 'Sion, Valais',
                'registration_url' => $defaultRegistrationUrl,
            ],
            'courses'         => $courses,
            'last_updated_at' => now('Europe/Zurich')->translatedFormat('d F Y à H:i'),
            'is_fallback'     => true,
        ];
    }
}
