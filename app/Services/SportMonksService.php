<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SportMonksService
{
    protected string $baseUrl;
    protected string $token;

    public function __construct()
    {
        $this->baseUrl = config('services.sportmonks.base_url');
        $this->token = config('services.sportmonks.token');
    }

    /**
     * Get all live scores
     */
    public function getLiveScores()
    {
        return Cache::remember('sportmonks_live', now()->addMinutes(1), function () {
            $response = Http::get(
                "{$this->baseUrl}/livescores",
                [
                    'api_token' => $this->token,
                    'include' => 'scores;participants;venue;league'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get fixtures for a specific date range
     */
    public function getFixtures($date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $cacheKey = "sportmonks_fixtures_{$date}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($date) {
            $response = Http::get(
                "{$this->baseUrl}/fixtures/date/{$date}",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;venue;league;season'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get Tanzania Premier League standings
     */
    public function getTanzaniaStandings()
    {
        return Cache::remember('sportmonks_tanzania_standings', now()->addHours(6), function () {
            $response = Http::get(
                "{$this->baseUrl}/standings",
                [
                    'api_token' => $this->token,
                    'filter' => json_encode(['league_id' => 12345]), // Replace with actual Tanzania league ID
                    'include' => 'participant'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get match details by fixture ID
     */
    public function getMatchDetails($fixtureId)
    {
        $cacheKey = "sportmonks_match_{$fixtureId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($fixtureId) {
            $response = Http::get(
                "{$this->baseUrl}/fixtures/{$fixtureId}",
                [
                    'api_token' => $this->token,
                    'include' => 'participants;scores;events;statistics;venue;league'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get team information
     */
    public function getTeam($teamId)
    {
        $cacheKey = "sportmonks_team_{$teamId}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($teamId) {
            $response = Http::get(
                "{$this->baseUrl}/teams/{$teamId}",
                [
                    'api_token' => $this->token,
                    'include' => 'players;coach;venue'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get leagues
     */
    public function getLeagues($search = null)
    {
        $cacheKey = "sportmonks_leagues_" . ($search ?? 'all');

        return Cache::remember($cacheKey, now()->addDay(), function () use ($search) {
            $params = [
                'api_token' => $this->token,
                'include' => 'season'
            ];

            if ($search) {
                $params['search'] = $search;
            }

            $response = Http::get(
                "{$this->baseUrl}/leagues",
                $params
            );
            
            return $response->json();
        });
    }

    /**
     * Get upcoming matches for a team
     */
    public function getTeamFixtures($teamId, $limit = 10)
    {
        $cacheKey = "sportmonks_team_fixtures_{$teamId}";

        return Cache::remember($cacheKey, now()->addHours(2), function () use ($teamId, $limit) {
            $response = Http::get(
                "{$this->baseUrl}/teams/{$teamId}/fixtures",
                [
                    'api_token' => $this->token,
                    'limit' => $limit,
                    'include' => 'participants;venue;league'
                ]
            );
            
            return $response->json();
        });
    }

    /**
     * Get top scorers for a league
     */
    public function getTopScorers($leagueId, $seasonId = null)
    {
        $cacheKey = "sportmonks_topscorers_{$leagueId}_{$seasonId}";

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($leagueId, $seasonId) {
            $params = [
                'api_token' => $this->token,
                'filter' => json_encode(['league_id' => $leagueId]),
                'include' => 'participant;team'
            ];

            if ($seasonId) {
                $params['filter'] = json_encode(['league_id' => $leagueId, 'season_id' => $seasonId]);
            }

            $response = Http::get(
                "{$this->baseUrl}/topscorers",
                $params
            );
            
            return $response->json();
        });
    }
}