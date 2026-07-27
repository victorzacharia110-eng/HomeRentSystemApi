<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\RoomPhoto;
use Illuminate\Http\Request;

class RoomPhotoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['room_id' => 'required|integer|exists:rooms,id']);

        $photos = RoomPhoto::where('room_id', $request->room_id)->get();

        $photos->each(function ($photo) {
            $photo->photo_url = $photo->photo;
        });

        return response()->json([
            'status' => 'success',
            'photos' => $photos,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|integer|exists:rooms,id',
            'photo'   => 'required|string|max:10240',
        ]);

        $roomPhoto = RoomPhoto::create([
            'room_id' => $request->room_id,
            'photo'   => $request->photo,
        ]);

        $roomPhoto->photo_url = $roomPhoto->photo;

        return response()->json([
            'status' => 'success',
            'photo'  => $roomPhoto,
        ], 201);
    }

    public function show(string $id)
    {
        $photo = RoomPhoto::findOrFail($id);
        $photo->photo_url = $photo->photo;

        return response()->json([
            'status' => 'success',
            'photo'  => $photo,
        ]);
    }

    public function destroy(string $id)
    {
        $photo = RoomPhoto::findOrFail($id);
        $photo->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Photo deleted successfully',
        ]);
    }
}
