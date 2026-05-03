<?php
namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of comments
     */
    public function index()
    {
        $comments = Comment::with('user')
            ->latest()
            ->get();

            return response()->json([
                'comments' => $comments
            ]);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request)
    {
        $request->validate([
            'comment' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $comment = Comment::create([
            'user_id' => auth()->user()->id,
            'comment' => $request->comment,
            'rating' => $request->rating,
        ]);

        return response()->json([
            'comment' => $comment
        ]);
    }

    /**
     * Display a single comment
     */
    public function show(string $id)
    {
        return Comment::with('user')->findOrFail($id);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, string $id)
    {
        $comment = Comment::findOrFail($id);

        $request->validate([
            'comment' => 'sometimes|string',
            'rating' => 'sometimes|integer|min:1|max:5',
        ]);

        $comment->update($request->only(['comment', 'rating']));

        return response()->json($comment);
    }

    /**
     * Delete a comment
     */
    public function destroy(string $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully'
        ]);
    }

    //  * Display the specified resource.


}
