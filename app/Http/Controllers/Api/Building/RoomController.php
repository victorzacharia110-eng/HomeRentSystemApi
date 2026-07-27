<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = [];

        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $roomsQuery = Room::with('user')->latest();
        if ($search) {
            $roomsQuery->where(function($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }
        $rooms = $roomsQuery->paginate($perPage);

        $rooms->getCollection()->transform(function ($room) {
            if ($room->photo) {
                $room->photo_url = Storage::disk('s3')->url($room->photo);
            }
            return $room;
        });

        $totalRooms = Room::count();
        $roomCountAvailable = Room::where('status', 'Available')->count();
        $roomCountOccupied = Room::where('status', 'Occupied')->count();
        $roomCountMaintanance = Room::where('status', 'Maintanance')->count();

        return response()->json([
            'rooms' => $rooms,
            'totalRooms' => $totalRooms,
            'roomsAvailableCount' => $roomCountAvailable,
            'roomsMaintananceCount' => $roomCountMaintanance,
            'roomsOccupiedCount' => $roomCountOccupied,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "room_number" => "required|string",
            "type" => "required|string",
            "status" => "required|string",
            "photo" => "nullable|image|max:2048"
        ]);

        $room = new Room();
        $room->room_number = $request->room_number;
        $room->type = $request->type;
        $room->status = $request->status;
        $room->room_price = $request->room_price;

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('rooms', 's3', 'public');
            $room->photo = $path;
        }

        $room->save();

        if ($room->photo) {
            $room->photo_url = Storage::disk('s3')->url($room->photo);
        }

        return response()->json([
            "status" => "success",
            "room" => $room
        ]);
    }

    public function show(string $id)
    {
        $room = Room::findOrFail($id);

        if ($room->photo) {
            $room->photo_url = Storage::disk('s3')->url($room->photo);
        }

        return response()->json([
            'room' => $room,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            "room_number" => "required|string",
            "type" => "required|string",
            "status" => "required|string",
            "photo" => "nullable|image|max:2048",
        ]);

        $room = Room::findOrFail($id);
        $room->room_number = $request->room_number;
        $room->type = $request->type;
        $room->status = $request->status;
        $room->room_price = $request->room_price;

        if ($request->hasFile('photo')) {
            if ($room->photo) {
                Storage::disk('s3')->delete($room->photo);
            }
            $path = $request->file('photo')->store('rooms', 's3', 'public');
            $room->photo = $path;
        }

        $room->save();

        if ($room->photo) {
            $room->photo_url = Storage::disk('s3')->url($room->photo);
        }

        return response()->json([
            "room" => $room,
        ]);
    }

    public function updateRoomStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:Available,Occupied,Maintenance',
        ]);

        $roomStatus = Room::findOrFail($id);
        $roomStatus->user_id = auth()->id();
        $roomStatus->status = $request->status;
        $roomStatus->save();

        $user = auth()->user();

        if ($request->status === 'Occupied') {
            $user->room_id = $roomStatus->id;
        } else {
            $user->room_id = null;
        }

        $user->save();

        return response()->json([
            "message" => "Room status updated successfully",
            "room" => $roomStatus,
        ]);
    }

    public function destroy(string $id)
    {
        $deleteRoom = Room::findOrFail($id);

        if ($deleteRoom->photo) {
            Storage::disk('s3')->delete($deleteRoom->photo);
        }

        $deleteRoom->delete();

        return response()->json([
            'deleteRoom' => $deleteRoom
        ]);
    }
}
