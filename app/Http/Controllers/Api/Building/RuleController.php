<?php

namespace App\Http\Controllers\Api\Building;

use App\Http\Controllers\Controller;
use App\Models\Rule;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $rulesQuery = Rule::query();
        if ($search) {
            $rulesQuery->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }
        $rules = $rulesQuery->paginate($perPage);
        return response()->json(['rules' => $rules]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|string',
        ]);

        $rule = new Rule();
        $rule->title = $request->title;
        $rule->description = $request->description;
        $rule->type = $request->type;

        $rule->save();

        return response()->json($rule, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $rule = Rule::find($id);
        return response()->json($rule);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|string',
        ]);
        $rule = Rule::findOrFail($id);
        $rule->title = $request->title;
        $rule->description = $request->description;
        $rule->type = $request->type;
        $rule->update();
        return response()->json(['rule' => $rule]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rule = Rule::findOrFail($id);
        $rule->delete();
        return response()->json($rule,200);

    }
}
