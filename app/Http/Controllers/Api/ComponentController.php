<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Component;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function index(): JsonResponse
    {
        $components = Component::with(['dependsOn.toComponent', 'documents'])->get();

        return response()->json($components);
    }

    public function show(Component $component): JsonResponse
    {
        $component->load(['dependsOn.toComponent', 'dependedBy.fromComponent', 'documents']);

        return response()->json([
            'component' => $component,
            'impacted'  => $component->getImpactedComponents(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'         => 'required|string|max:32|unique:components',
            'name'         => 'required|string|max:255',
            'name_en'      => 'required|string|max:255',
            'category'     => 'required|in:magnet,structure,thermal,plasma',
            'description'  => 'nullable|string',
            'owner_team'   => 'required|string|max:255',
            'owner_avatar' => 'nullable|string|max:8',
            'specs'        => 'nullable|array',
        ]);

        $component = Component::create($validated);

        return response()->json($component, 201);
    }

    public function update(Request $request, Component $component): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'name_en'      => 'sometimes|string|max:255',
            'category'     => 'sometimes|in:magnet,structure,thermal,plasma',
            'description'  => 'nullable|string',
            'owner_team'   => 'sometimes|string|max:255',
            'owner_avatar' => 'nullable|string|max:8',
            'specs'        => 'nullable|array',
        ]);

        $component->update($validated);

        return response()->json($component);
    }

    public function graph(): JsonResponse
    {
        $components = Component::all();
        $dependencies = \App\Models\Dependency::with(['fromComponent', 'toComponent'])->get();

        return response()->json([
            'nodes' => $components,
            'edges' => $dependencies,
        ]);
    }
}