<?php

namespace App\Http\Controllers\Api\Entertainment\Football;

use App\Http\Controllers\Controller;
use App\Services\SportMonksService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FootballController extends Controller
{
    protected SportMonksService $sportmonks;

    public function __construct(SportMonksService $sportmonks)
    {
        $this->sportmonks = $sportmonks;
    }

    /**
     * Get live scores from all available leagues
     */
    public function live()
    {
        try {
            $data = $this->sportmonks->getLiveScores();
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => []
                ], 500);
            }

            // Get all live matches
            $liveMatches = $data['data'] ?? [];
            
            // Group by league for better organization
            $grouped = [];
            foreach ($liveMatches as $match) {
                $leagueName = $match['league']['name'] ?? 'Unknown League';
                if (!isset($grouped[$leagueName])) {
                    $grouped[$leagueName] = [];
                }
                $grouped[$leagueName][] = $match;
            }

            return response()->json([
                'success' => true,
                'data' => $liveMatches,
                'grouped' => $grouped,
                'meta' => [
                    'count' => count($liveMatches),
                    'leagues' => array_keys($grouped),
                    'timestamp' => now()->toIso8601String(),
                    'pagination' => $data['pagination'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football live error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get fixtures from all available leagues
     */
    public function fixtures(Request $request)
    {
        try {
            $date = $request->input('date');
            $data = $this->sportmonks->getFixtures($date);
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => []
                ], 500);
            }

            $fixtures = $data['data'] ?? [];
            
            // Group by league
            $grouped = [];
            foreach ($fixtures as $fixture) {
                $leagueName = $fixture['league']['name'] ?? 'Unknown League';
                if (!isset($grouped[$leagueName])) {
                    $grouped[$leagueName] = [];
                }
                $grouped[$leagueName][] = $fixture;
            }

            return response()->json([
                'success' => true,
                'data' => $fixtures,
                'grouped' => $grouped,
                'meta' => [
                    'count' => count($fixtures),
                    'leagues' => array_keys($grouped),
                    'date' => $date ?? now()->format('Y-m-d'),
                    'pagination' => $data['pagination'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football fixtures error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    // ... rest of your controller methods remain the same
}