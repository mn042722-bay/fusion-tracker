<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChangeImpact;
use App\Models\ChangeNotification;
use App\Models\Component;
use App\Models\DesignChange;
use App\Services\ImpactAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesignChangeController extends Controller
{
    public function __construct(
        private ImpactAnalysisService $analysisService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = DesignChange::with('component')
            ->orderByDesc('created_at');

        if ($request->has('component_id')) {
            $query->where('component_id', $request->component_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $changes = $query->paginate($request->get('per_page', 20));

        return response()->json($changes);
    }

    public function show(DesignChange $designChange): JsonResponse
    {
        $designChange->load([
            'component',
            'impacts.component',
            'notifications',
        ]);

        return response()->json($designChange);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'component_id' => 'required|exists:components,id',
            'title'        => 'required|string|max:255',
            'description'  => 'required|string',
            'author'       => 'required|string|max:255',
        ]);

        $change = DesignChange::create([
            'change_code'  => DesignChange::nextCode(),
            'component_id' => $validated['component_id'],
            'title'        => $validated['title'],
            'description'  => $validated['description'],
            'author'       => $validated['author'],
            'status'       => 'pending',
        ]);

        $analysis = $this->analysisService->analyze($change);

        foreach ($analysis['impacted'] as $impact) {
            ChangeImpact::create([
                'design_change_id' => $change->id,
                'component_id'     => $impact['id'],
                'strength'         => $impact['strength'],
                'relation'         => $impact['relation'],
                'depth'            => $impact['depth'],
            ]);
        }

        $notifications = [];
        foreach ($analysis['impacted'] as $impact) {
            if (in_array($impact['strength'], ['high', 'medium'])) {
                $comp = Component::find($impact['id']);
                $strengthLabel = $impact['strength'] === 'high' ? '要即対応' : '確認依頼';
                $sourceComp = $change->component;

                $notif = ChangeNotification::create([
                    'design_change_id' => $change->id,
                    'component_id'     => $impact['id'],
                    'team'             => $comp->owner_team,
                    'strength'         => $impact['strength'],
                    'message'          => "{$sourceComp->name}の設計変更「{$change->title}」により、{$comp->name}への影響確認が必要です（{$strengthLabel}）",
                ]);
                $notifications[] = $notif;
            }
        }

        $change->update([
            'ai_summary'      => $analysis['ai_summary'],
            'ai_raw_response' => $analysis,
            'analyzed_at'     => now(),
        ]);

        $change->load(['component', 'impacts.component', 'notifications']);

        return response()->json([
            'change'        => $change,
            'ai_summary'    => $analysis['ai_summary'],
            'documents'     => $analysis['documents'],
            'notifications' => $notifications,
        ], 201);
    }

    public function updateStatus(Request $request, DesignChange $designChange): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,approved,rejected',
        ]);

        $designChange->update($validated);

        return response()->json($designChange);
    }

    public function reanalyze(DesignChange $designChange): JsonResponse
    {
        $designChange->impacts()->delete();
        $designChange->notifications()->delete();

        $analysis = $this->analysisService->analyze($designChange);

        foreach ($analysis['impacted'] as $impact) {
            ChangeImpact::create([
                'design_change_id' => $designChange->id,
                'component_id'     => $impact['id'],
                'strength'         => $impact['strength'],
                'relation'         => $impact['relation'],
                'depth'            => $impact['depth'],
            ]);
        }

        $designChange->update([
            'ai_summary'      => $analysis['ai_summary'],
            'ai_raw_response' => $analysis,
            'analyzed_at'     => now(),
        ]);

        $designChange->load(['component', 'impacts.component', 'notifications']);

        return response()->json([
            'change'     => $designChange,
            'ai_summary' => $analysis['ai_summary'],
            'documents'  => $analysis['documents'],
        ]);
    }
}