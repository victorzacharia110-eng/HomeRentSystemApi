<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // $user = auth()->user();

        // fetch rooms only for landlord
        $rooms = [];

        // if ($user->is_landlord) {
        $rooms = Room::with('user')->latest()->get();
        // }

        // stats visible to everyone
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

    /**
     * Store a newly created resource in storage.
     */
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
            $path = $request->file('photo')->store('rooms', 'public');
            $room->photo = $path;
        }

        $room->save();

        return response()->json([
            "status" => "success",
            "room" => $room
        ]);
    }

    /**
     * Display the specified resource.
     */ public function show(string $id)
    {
        $room = Room::findOrFail($id);

        return response()->json([
            'room' => $room,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            "room_number" => "required|string",
            "type" => "required|string",
            "status" => "required|string",
        ]);
        $room = Room::find($id);
        $room->room_number = $request->room_number;
        $room->type = $request->type;
        $room->status = $request->status;

        $room->update();
        return response()->json([
            "room" => $room,
        ]);
    }

    public function updateRoomStatus(Request $request, string $id)
    {
        // Validate input
        $request->validate([
            'status' => 'required|in:Available,Occupied,Maintenance',
        ]);

        // Find room or fail automatically
        $roomStatus = Room::findOrFail($id);

        // Update room
        $roomStatus->user_id = auth()->id();
        $roomStatus->status = $request->status;
        $roomStatus->save();

        // Get logged-in user
        $user = auth()->user();

        // Assign or remove room based on status
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
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $deleteRoom = Room::findOrFail($id);
        $deleteRoom->delete();

        return response()->json([
            'deleteRoom' => $deleteRoom
        ]);
    }
}
