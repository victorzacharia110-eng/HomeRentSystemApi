<?php

namespace App\Http\Controllers\Api\Entertainment\Football;

use App\Http\Controllers\Controller;
use App\Services\SportMonksService;
use Illuminate\Http\Request;

class FootballController extends Controller
{
       protected SportMonksService $sportmonks;

    public function __construct(SportMonksService $sportmonks)
    {
        $this->sportmonks = $sportmonks;
    }

    public function live()
    {
        $data = $this->sportmonks->getLiveScores();
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }

    public function fixtures(Request $request)
    {
        $date = $request->input('date');
        $data = $this->sportmonks->getFixtures($date);
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }

    public function standings()
    {
        $data = $this->sportmonks->getTanzaniaStandings();
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }

    public function match($fixtureId)
    {
        $data = $this->sportmonks->getMatchDetails($fixtureId);
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }

    public function team($teamId)
    {
        $data = $this->sportmonks->getTeam($teamId);
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }

    public function scorers()
    {
        // Replace with actual Tanzania league ID
        $tanzaniaLeagueId = 12345;
        $data = $this->sportmonks->getTopScorers($tanzaniaLeagueId);
        return response()->json([
            'success' => true,
            'data' => $data['data'] ?? []
        ]);
    }
}
