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
                    'pagination' => $data['pagination'] ?? null,
                    'rate_limit' => $data['rate_limit'] ?? null
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
                    'pagination' => $data['pagination'] ?? null,
                    'rate_limit' => $data['rate_limit'] ?? null
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

    /**
     * Get standings from all available leagues
     */
    public function standings(Request $request)
    {
        try {
            $leagueId = $request->input('league_id');
            
            // If specific league requested
            if ($leagueId) {
                $data = $this->sportmonks->getStandingsByLeague($leagueId);
                
                if (isset($data['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $data['error'],
                        'data' => []
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'data' => $data['data'] ?? [],
                    'meta' => [
                        'league_id' => $leagueId,
                        'source' => 'sportmonks'
                    ]
                ]);
            }

            // Get all available leagues and their standings
            $leagues = $this->sportmonks->getAvailableLeagues();
            $allStandings = [];
            
            foreach ($leagues['data'] ?? [] as $league) {
                $standings = $this->sportmonks->getStandingsByLeague($league['id']);
                if (!empty($standings['data'])) {
                    $allStandings[$league['name']] = [
                        'league_id' => $league['id'],
                        'standings' => $standings['data']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $allStandings,
                'meta' => [
                    'total_leagues' => count($allStandings),
                    'leagues' => array_keys($allStandings)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football standings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get all available leagues
     */
    public function leagues()
    {
        try {
            $data = $this->sportmonks->getAvailableLeagues();
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => []
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? [],
                'meta' => [
                    'count' => count($data['data'] ?? 0),
                    'rate_limit' => $data['rate_limit'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football leagues error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get match details
     */
    public function match($fixtureId)
    {
        try {
            $data = $this->sportmonks->getMatchDetails($fixtureId);
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => null
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? null,
                'meta' => [
                    'rate_limit' => $data['rate_limit'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football match error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get team details
     */
    public function team($teamId)
    {
        try {
            $data = $this->sportmonks->getTeam($teamId);
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => null
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? null,
                'meta' => [
                    'rate_limit' => $data['rate_limit'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football team error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get top scorers from all available leagues
     */
    public function scorers(Request $request)
    {
        try {
            $leagueId = $request->input('league_id');
            
            // If specific league requested
            if ($leagueId) {
                $data = $this->sportmonks->getTopScorers($leagueId);
                
                if (isset($data['error'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $data['error'],
                        'data' => []
                    ], 500);
                }

                return response()->json([
                    'success' => true,
                    'data' => $data['data'] ?? [],
                    'meta' => [
                        'league_id' => $leagueId,
                        'rate_limit' => $data['rate_limit'] ?? null
                    ]
                ]);
            }

            // Get top scorers from all available leagues
            $leagues = $this->sportmonks->getAvailableLeagues();
            $allScorers = [];
            
            foreach ($leagues['data'] ?? [] as $league) {
                $scorers = $this->sportmonks->getTopScorers($league['id']);
                if (!empty($scorers['data'])) {
                    $allScorers[$league['name']] = [
                        'league_id' => $league['id'],
                        'scorers' => $scorers['data']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $allScorers,
                'meta' => [
                    'total_leagues' => count($allScorers),
                    'leagues' => array_keys($allScorers)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Football scorers error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Test the SportMonks API connection
     */
    public function testConnection()
    {
        try {
            $result = $this->sportmonks->testConnection();
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['data'] ?? null,
                'token_configured' => !empty(config('services.sportmonks.token')),
                'token_preview' => substr(config('services.sportmonks.token') ?? '', 0, 10) . '...'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}