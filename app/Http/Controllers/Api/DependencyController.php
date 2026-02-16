<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dependency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DependencyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Dependency::with(['fromComponent', 'toComponent'])->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_component_id' => 'required|exists:components,id',
            'to_component_id'   => 'required|exists:components,id|different:from_component_id',
            'relation'          => 'required|string|max:255',
            'strength'          => 'required|in:high,medium,low',
        ]);

        $dep = Dependency::create($validated);
        $dep->load(['fromComponent', 'toComponent']);

        return response()->json($dep, 201);
    }

    public function update(Request $request, Dependency $dependency): JsonResponse
    {
        $validated = $request->validate([
            'relation' => 'sometimes|string|max:255',
            'strength' => 'sometimes|in:high,medium,low',
        ]);

        $dependency->update($validated);

        return response()->json($dependency);
    }

    public function destroy(Dependency $dependency): JsonResponse
    {
        $dependency->delete();

        return response()->json(null, 204);
    }
}