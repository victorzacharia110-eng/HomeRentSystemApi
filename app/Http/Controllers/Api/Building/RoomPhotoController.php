<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\RoomPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomPhotoController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['room_id' => 'required|integer|exists:rooms,id']);

        $photos = RoomPhoto::where('room_id', $request->room_id)->get();

        $photos->each(function ($photo) {
            $photo->photo_url = Storage::disk('s3')->url($photo->photo);
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
            'photo'   => 'required|image|max:5120',
        ]);

        $path = $request->file('photo')->store('rooms', 's3', 'public');

        $roomPhoto = RoomPhoto::create([
            'room_id' => $request->room_id,
            'photo'   => $path,
        ]);

        $roomPhoto->photo_url = Storage::disk('s3')->url($path);

        return response()->json([
            'status' => 'success',
            'photo'  => $roomPhoto,
        ], 201);
    }

    public function show(string $id)
    {
        $photo = RoomPhoto::findOrFail($id);
        $photo->photo_url = Storage::disk('s3')->url($photo->photo);

        return response()->json([
            'status' => 'success',
            'photo'  => $photo,
        ]);
    }

    public function destroy(string $id)
    {
        $photo = RoomPhoto::findOrFail($id);

        Storage::disk('s3')->delete($photo->photo);

        $photo->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Photo deleted successfully',
        ]);
    }
}
