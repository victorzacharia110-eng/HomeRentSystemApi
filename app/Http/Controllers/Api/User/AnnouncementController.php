<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Announcement;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display all announcements (latest first)
     */
    public function index()
    {
        $announcements = Announcement::latest()->get();
        return response()->json([
            'announcements' => $announcements
        ]);
    }

    /**
     * Store a newly created announcement
     */
    public function store(Request $request)
    {
        $request->validate([
            
            'title' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $announcement = Announcement::create([
            'user_id' => auth()->user()->id,
            'title' => $request->title,
            'message' => $request->message,
        ]);

        return response()->json([
            'announcement' => $announcement
        ]);
    }

    /**
     * Show single announcement
     */
    public function show(string $id)
    {
        return Announcement::findOrFail($id);
    }

    /**
     * Update announcement
     */
    public function update(Request $request, string $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
        ]);

        $announcement->update($request->only(['title', 'message']));

        return response()->json($announcement);
    }

    /**
     * Delete announcement
     */
    public function destroy(string $id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}