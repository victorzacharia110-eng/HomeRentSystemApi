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

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? [],
                'meta' => [
                    'count' => count($data['data'] ?? 0),
                    'timestamp' => now()->toIso8601String()
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

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? []
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

    public function standings()
    {
        try {
            $data = $this->sportmonks->getTanzaniaStandings();
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error'],
                    'data' => []
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $data['data'] ?? []
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
                'data' => $data['data'] ?? null
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
                'data' => $data['data'] ?? null
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

    public function scorers()
    {
        try {
            $leagueId = config('services.sportmonks.tanzania_league_id', 12345);
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
                'data' => $data['data'] ?? []
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
}