<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Services\DatasportScraperService;

class RunFillRateController extends Controller
{
    public function __construct(
        protected DatasportScraperService $scraperService
    ) {}

    /**
     * Affiche la page publique du taux de remplissage des courses (supporte ?embed=1 pour iframe).
     */
    public function index(Request $request): Response
    {
        $forceRefresh = $request->boolean('refresh');
        $data = $this->scraperService->getFillRates($forceRefresh);
        $isEmbed = $request->boolean('embed') || $request->query('embed') === '1';

        $content = view('front.runs-fill-rate', [
            'event'         => $data['event'],
            'courses'       => $data['courses'],
            'lastUpdatedAt' => $data['last_updated_at'],
            'isFallback'    => $data['is_fallback'],
            'isEmbed'       => $isEmbed,
        ])->render();

        return response($content)
            ->header('X-Frame-Options', 'ALLOWALL')
            ->header('Content-Security-Policy', 'frame-ancestors *;');
    }

    /**
     * Endpoint API JSON public pour widgets ou scripts tiers (avec support CORS complet).
     */
    public function api(Request $request): JsonResponse
    {
        $forceRefresh = $request->boolean('refresh');
        $data = $this->scraperService->getFillRates($forceRefresh);

        return response()->json([
            'success'   => true,
            'data'      => $data,
            'cached_at' => now()->toIso8601String(),
        ], 200, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
        ]);
    }
}
