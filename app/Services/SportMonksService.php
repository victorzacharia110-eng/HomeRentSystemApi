<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SportMonksService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.sportmonks.base_url', 'https://api.sportmonks.com/v3/football');
        $this->token = config('services.sportmonks.token');

        if (empty($this->token)) {
            Log::error('SportMonks API token is not configured!');
        }
    }

    /**
     * Test the API connection
     */
    public function testConnection()
    {
        try {
            if (empty($this->token)) {
                return ['success' => false, 'message' => 'API token is not configured'];
            }

            $response = Http::timeout(10)->get(
                "{$this->baseUrl}/leagues",
                [
                    'api_token' => $this->token,
                    'per_page' => 1
                ]
            );

            if ($response->failed()) {
                return [
                    'success' => false,
                    'message' => 'API connection failed: ' . $response->status(),
                    'body' => $response->body()
                ];
            }

            $data = $response->json();
            return [
                'success' => true,
                'message' => 'API connection successful',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
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
                    return ['data' => [], 'error' => 'API request failed: ' . $response->body()];
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

            if (empty($leagueId)) {
                return ['data' => [], 'error' => 'League ID is required'];
            }

            $cacheKey = "sportmonks_standings_{$leagueId}";

            return Cache::remember($cacheKey, now()->addHours(6), function () use ($leagueId) {
                $params = [
                    'api_token' => $this->token,
                    'filter[league_id]' => $leagueId,
                    'include' => 'participant;league;season;stage'
                ];

                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/standings",
                    $params
                );

                if ($response->failed()) {
                    Log::error('SportMonks Standings Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'league_id' => $leagueId
                    ]);
                    return ['data' => [], 'error' => 'API request failed: ' . $response->body()];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get all live scores
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
                    'include' => 'participants;scores;league;venue;season;state'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks Live Scores Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return ['data' => [], 'error' => 'API returned status ' . $response->status() . ': ' . $response->body()];
            }

            $data = $response->json();

            // Filter to only include in-progress or upcoming matches
            if (isset($data['data'])) {
                $data['data'] = array_filter($data['data'], function ($fixture) {
                    // Only include matches that are not finished (state_id 1,2,3,4)
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
     * Get fixtures for a specific date
     */
    public function getFixtures($date = null)
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            $date = $date ?? now()->format('Y-m-d');

            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return ['data' => [], 'error' => 'Invalid date format. Use YYYY-MM-DD'];
            }

            $cacheKey = "sportmonks_fixtures_{$date}";

            return Cache::remember($cacheKey, now()->addHours(2), function () use ($date) {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/fixtures/date/{$date}",
                    [
                        'api_token' => $this->token,
                        'include' => 'participants;venue;league;season;state;scores;comments'
                    ]
                );

                if ($response->failed()) {
                    Log::error('SportMonks Fixtures Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'date' => $date
                    ]);
                    return ['data' => [], 'error' => 'API returned status ' . $response->status() . ': ' . $response->body()];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get fixtures with filters
     */
    public function getFixturesWithFilters($filters = [])
    {
        try {
            if (empty($this->token)) {
                return ['data' => [], 'error' => 'API token is not configured'];
            }

            // Build query parameters using SportMonks v3 filter format
            $params = [
                'api_token' => $this->token,
                'include' => 'participants;venue;league;season;state;scores;comments'
            ];

            // Add filters in proper format: filter[field]=value
            foreach ($filters as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        $params["filter[{$key}][]"] = $item;
                    }
                } else {
                    $params["filter[{$key}]"] = $value;
                }
            }

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/fixtures",
                $params
            );

            if ($response->failed()) {
                Log::error('SportMonks Fixtures With Filters Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'filters' => $filters
                ]);
                return ['data' => [], 'error' => 'API request failed: ' . $response->body()];
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
                return ['data' => null, 'error' => 'API token is not configured'];
            }

            if (empty($fixtureId)) {
                return ['data' => null, 'error' => 'Fixture ID is required'];
            }

            
            if (!is_numeric($fixtureId)) {
                return ['data' => null, 'error' => 'Invalid Fixture ID format'];
            }

            $cacheKey = "sportmonks_match_{$fixtureId}";

            return Cache::remember($cacheKey, now()->addHours(1), function () use ($fixtureId) {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/fixtures/{$fixtureId}",
                    [
                        'api_token' => $this->token,
                        'include' => 'participants;scores;events;statistics;venue;league;season;state;comments'
                    ]
                );

                if ($response->failed()) {
                    Log::error('SportMonks Match Details Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'fixture_id' => $fixtureId
                    ]);
                    return ['data' => null, 'error' => 'API returned status ' . $response->status() . ': ' . $response->body()];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get team information
     */
    public function getTeam($teamId)
    {
        try {
            if (empty($this->token)) {
                return ['data' => null, 'error' => 'API token is not configured'];
            }

            if (empty($teamId)) {
                return ['data' => null, 'error' => 'Team ID is required'];
            }

            if (!is_numeric($teamId)) {
                return ['data' => null, 'error' => 'Invalid Team ID format'];
            }

            $cacheKey = "sportmonks_team_{$teamId}";

            return Cache::remember($cacheKey, now()->addHours(24), function () use ($teamId) {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/teams/{$teamId}",
                    [
                        'api_token' => $this->token,
                        'include' => 'players;coach;venue;season;league'
                    ]
                );

                if ($response->failed()) {
                    Log::error('SportMonks Team Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'team_id' => $teamId
                    ]);
                    return ['data' => null, 'error' => 'API returned status ' . $response->status() . ': ' . $response->body()];
                }

                return $response->json();
            });
        } catch (\Exception $e) {
            Log::error('SportMonks Exception: ' . $e->getMessage());
            return ['data' => null, 'error' => $e->getMessage()];
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

            if (empty($leagueId)) {
                return ['data' => [], 'error' => 'League ID is required'];
            }

            $cacheKey = "sportmonks_topscorers_{$leagueId}";

            return Cache::remember($cacheKey, now()->addHours(6), function () use ($leagueId) {
                $params = [
                    'api_token' => $this->token,
                    'filter[league_id]' => $leagueId,
                    'include' => 'participant;team;season;league'
                ];

                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/topscorers",
                    $params
                );

                if ($response->failed()) {
                    Log::error('SportMonks Top Scorers Error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'league_id' => $leagueId
                    ]);
                    return ['data' => [], 'error' => 'API returned status ' . $response->status() . ': ' . $response->body()];
                }

                return $response->json();
            });
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
