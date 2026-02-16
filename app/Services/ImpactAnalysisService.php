<?php

namespace App\Services;

use App\Models\Component;
use App\Models\DesignChange;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImpactAnalysisService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-20250514');
    }

    public function analyze(DesignChange $change): array
    {
        $component = $change->component;
        $impacted  = $component->getImpactedComponents();
        $documents = $this->getAffectedDocuments($component, $impacted);

        $aiSummary = $this->callClaude($component, $change, $impacted, $documents);

        return [
            'impacted'   => $impacted,
            'documents'  => $documents,
            'ai_summary' => $aiSummary,
        ];
    }

    private function getAffectedDocuments(Component $source, array $impacted): array
    {
        $componentIds = array_merge(
            [$source->id],
            array_column($impacted, 'id')
        );

        return Document::whereHas('components', function ($q) use ($componentIds) {
            $q->whereIn('components.id', $componentIds);
        })->get()->map(function ($doc) {
            return [
                'id'       => $doc->id,
                'name'     => $doc->name,
                'doc_type' => $doc->doc_type,
                'version'  => $doc->version,
            ];
        })->toArray();
    }

    private function callClaude(
        Component $component,
        DesignChange $change,
        array $impacted,
        array $documents
    ): string {
        if (empty($this->apiKey)) {
            Log::info('Anthropic API key not set, using fallback summary');
            return $this->generateFallbackSummary($component, $change, $impacted, $documents);
        }

        $prompt = $this->buildPrompt($component, $change, $impacted, $documents);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 2000,
                'system'     => $this->systemPrompt(),
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'][0]['text'] ?? 'AI分析結果を取得できませんでした。';
            }

            Log::error('Claude API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return $this->generateFallbackSummary($component, $change, $impacted, $documents);

        } catch (\Exception $e) {
            Log::error('Claude API exception', ['message' => $e->getMessage()]);
            return $this->generateFallbackSummary($component, $change, $impacted, $documents);
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
あなたは核融合炉の設計変更影響分析の専門AIアシスタントです。
設計変更の内容と影響を受けるコンポーネントの情報をもとに、
エンジニアが即座にアクションを取れる実用的な分析レポートを生成してください。

レポートは以下の構成で出力してください:
1. 変更影響サマリー（概要を2-3文で）
2. 影響を受けるコンポーネント一覧（影響度・理由つき）
3. 推奨アクション（優先度順、担当チーム名つき）
4. 要更新ドキュメント一覧
5. リスク評価（見落としやすいリスクがあれば指摘）

Markdown形式で出力してください。
PROMPT;
    }

    private function buildPrompt(
        Component $component,
        DesignChange $change,
        array $impacted,
        array $documents
    ): string {
        $impactedText = collect($impacted)->map(function ($imp) {
            $depthLabel = $imp['depth'] === 1 ? '直接' : '間接';
            return "- {$imp['name']}（影響度: {$imp['strength']}, {$depthLabel}）: {$imp['relation']}";
        })->implode("\n");

        $docsText = collect($documents)->map(function ($doc) {
            return "- {$doc['name']}（{$doc['doc_type']}）";
        })->implode("\n");

        return <<<PROMPT
## 設計変更情報

**変更対象コンポーネント**: {$component->name}（{$component->name_en}）
**担当チーム**: {$component->owner_team}
**変更タイトル**: {$change->title}
**変更内容**: {$change->description}

## 影響を受けるコンポーネント（依存関係グラフより）

{$impactedText}

## 関連ドキュメント

{$docsText}

上記の情報をもとに、設計変更影響分析レポートを生成してください。
PROMPT;
    }

    private function generateFallbackSummary(
        Component $component,
        DesignChange $change,
        array $impacted,
        array $documents
    ): string {
        $strengthLabels = ['high' => '高', 'medium' => '中', 'low' => '低'];

        $impactLines = collect($impacted)->map(function ($imp) use ($strengthLabels) {
            $label = $strengthLabels[$imp['strength']];
            return "- **{$imp['name']}**（影響度: {$label}）— {$imp['relation']}";
        })->implode("\n");

        $highImpacts = collect($impacted)->filter(fn($i) => $i['strength'] === 'high');
        $actionLines = $highImpacts->map(function ($imp) {
            $comp = Component::find($imp['id']);
            return "1. {$imp['name']}の設計チーム（{$comp->owner_team}）と即座に設計レビューを実施";
        })->implode("\n");

        $medImpacts = collect($impacted)->filter(fn($i) => $i['strength'] === 'medium');
        $medLines = $medImpacts->map(function ($imp) {
            return "2. {$imp['name']}への影響評価を1週間以内に完了";
        })->implode("\n");

        $docLines = collect($documents)->map(function ($doc) {
            return "- 📄 {$doc['name']}（{$doc['doc_type']}）";
        })->implode("\n");

        $impactedCount = count($impacted);

        return <<<SUMMARY
## 変更影響サマリー

**変更対象**: {$component->name}（{$component->name_en}）
**変更内容**: {$change->description}

### 影響を受けるコンポーネント（{$impactedCount}件）
{$impactLines}

### 推奨アクション
{$actionLines}
{$medLines}

### 要更新ドキュメント
{$docLines}
SUMMARY;
    }
}