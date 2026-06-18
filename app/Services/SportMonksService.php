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
        $cacheKey = "sportmonks_available_leagues";

        return Cache::remember($cacheKey, now()->addDay(), function () {
            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/leagues",
                [
                    'api_token' => $this->token,
                    'include' => 'season'
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
    }

    /**
     * Get standings by league ID
     */
    public function getStandingsByLeague($leagueId)
    {
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
                return ['data' => [], 'error' => 'API request failed'];
            }
            
            return $response->json();
        });
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

            $response = Http::timeout(30)->get(
                "{$this->baseUrl}/livescores",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;scores;league;venue;season;timer'
                ]
            );

            if ($response->failed()) {
                Log::error('SportMonks API Error', [
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
     * Get fixtures for a specific date
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
                    'include' => 'participants;venue;league;season'
                ]
            );

            if ($response->failed()) {
                return ['data' => [], 'error' => 'API returned status ' . $response->status()];
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
                    'include' => 'participants;scores;events;statistics;venue;league;season'
                ]
            );

            if ($response->failed()) {
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