<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SportMonksService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.sportmonks.base_url', 'https://api.sportmonks.com/v3/football');
        $this->token = config('services.sportmonks.token');

        if (empty($this->token)) {
            Log::error('SportMonks API token is not configured!');
        }
    }

    /**
     * Get all available leagues in your subscription
     */
    public function getAvailableLeagues()
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $cacheKey = "sportmonks_available_leagues";

            return Cache::remember($cacheKey, now()->addHours(6), function () {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/leagues",
                    [
                        'api_token' => $this->token,
                        'include' => 'season;stage'
                    ]
                );

                if ($response->failed()) {
                    Log::error('SportMonks Leagues Error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return ['data' => [], 'error' => 'API request failed'];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get standings by league ID
     */
    public function getStandingsByLeague($leagueId)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $cacheKey = "sportmonks_standings_{$leagueId}";

            return Cache::remember($cacheKey, now()->addHours(6), function () use ($leagueId) {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/standings",
                    [
                        'api_token' => $this->token,
                        'filter' => json_encode(['league_id' => $leagueId]),
                        'include' => 'participant;league'
                    ]
                );

                if ($response->failed()) {
                    Log::error('SportMonks Standings Error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return ['data' => [], 'error' => 'API request failed'];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all live scores - FIXED for SportMonks v3
     */
    public function getLiveScores()
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            // Get current date for live matches
            $today = now()->format('Y-m-d');
            
            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/fixtures/date/{$today}",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;scores;league;venue;season;timer;state',
                    'filters' => json_encode([
                        'state_id' => [1, 2, 3, 4, 5] // 1=Not Started, 2=In Progress, 3=Halftime, 4=Extra Time, 5=Full Time
                    ])
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Live Scores Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
            }

            $data = $response->json();
            
            // Filter to only include in-progress or upcoming matches
            if (isset($data['data'])) {
                $data['data'] = array_filter($data['data'], function($fixture) {
                    // Only include matches that are not finished
                    return in_array($fixture['state_id'] ?? 0, [1, 2, 3, 4]);
                });
                // Re-index array
                $data['data'] = array_values($data['data']);
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get fixtures for a specific date - FIXED for SportMonks v3
     */
    public function getFixtures($date = null)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $date = $date ?? now()->format('Y-m-d');

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/fixtures/date/{$date}",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;venue;league;season;state;scores;timer'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Fixtures Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get fixtures with filters (like you tested with Thunderclient)
     */
    public function getFixturesWithFilters($filters = [])
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $params = [
                'api_token' => $this->token,
                'include' => 'participants;venue;league;season;state;scores;timer'
            ];

            // Add filters if provided
            if (!empty($filters)) {
                $params['filters'] = json_encode($filters);
            }

            // Add pagination if needed
            if (isset($filters['page'])) {
                $params['page'] = $filters['page'];
            }

            // You can also use the direct fixtures endpoint with filters
            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/fixtures",
                $params
            );

            if ($response->failed()) {
                Log::error('SportMonks Fixtures Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API request failed'];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get match details by fixture ID
     */
    public function getMatchDetails($fixtureId)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/fixtures/{$fixtureId}",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;scores;events;statistics;venue;league;season;state;timer'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Match Details Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get team information
     */
    public function getTeam($teamId)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/teams/{$teamId}",
                [
                    'api_token' => $this->token,
                    'include' => 'players;coach;venue'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Team Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get top scorers for a league
     */
    public function getTopScorers($leagueId)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/topscorers",
                [
                    'api_token' => $this->token,
                    'filter' => json_encode(['league_id' => $leagueId]),
                    'include' => 'participant;team'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Top Scorers Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Tanzania standings (legacy method - kept for compatibility)
     */
    public function getTanzaniaStandings()
    {
        $leagueId = config('services.sportmonks.tanzania_league_id', 12345);
        return $this->getStandingsByLeague($leagueId);
    }
}