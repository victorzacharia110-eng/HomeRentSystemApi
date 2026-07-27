<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
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
            $room->photo_url = $room->photo ?: null;
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
            "photo" => "nullable|string|max:10240",
        ]);

        $room = new Room();
        $room->room_number = $request->room_number;
        $room->type = $request->type;
        $room->status = $request->status;
        $room->room_price = $request->room_price;

        if ($request->filled('photo')) {
            $room->photo = $request->photo;
        }

        $room->save();

        $room->photo_url = $room->photo ?: null;

        return response()->json([
            "status" => "success",
            "room" => $room
        ]);
    }

    public function show(string $id)
    {
        $room = Room::findOrFail($id);

        $room->photo_url = $room->photo ?: null;

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
            "photo" => "nullable|string|max:10240",
        ]);

        $room = Room::findOrFail($id);
        $room->room_number = $request->room_number;
        $room->type = $request->type;
        $room->status = $request->status;
        $room->room_price = $request->room_price;

        if ($request->has('photo')) {
            $room->photo = $request->photo ?: null;
        }

        $room->save();

        $room->photo_url = $room->photo ?: null;

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
        $deleteRoom->delete();

        return response()->json([
            'deleteRoom' => $deleteRoom
        ]);
    }
}
