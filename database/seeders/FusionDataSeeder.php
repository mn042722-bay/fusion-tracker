<?php

namespace Database\Seeders;

use App\Models\Component;
use App\Models\Dependency;
use App\Models\Document;
use Illuminate\Database\Seeder;

class FusionDataSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            ['code' => 'tfc',     'name' => 'TFコイル',       'name_en' => 'Toroidal Field Coil',  'category' => 'magnet',    'description' => '超伝導トロイダル磁場コイル。REBCO高温超伝導体を使用。',               'owner_team' => '磁場設計チーム',      'owner_avatar' => '🧲', 'specs' => ['magneticField' => '12.2 T', 'temperature' => '20 K', 'material' => 'REBCO HTS']],
            ['code' => 'pfc',     'name' => 'PFコイル',       'name_en' => 'Poloidal Field Coil',  'category' => 'magnet',    'description' => 'プラズマの位置・形状制御用ポロイダル磁場コイル。',                     'owner_team' => '磁場設計チーム',      'owner_avatar' => '🧲', 'specs' => ['magneticField' => '6.0 T', 'temperature' => '20 K', 'material' => 'REBCO HTS']],
            ['code' => 'vv',      'name' => '真空容器',       'name_en' => 'Vacuum Vessel',        'category' => 'structure', 'description' => 'プラズマを閉じ込める二重壁ステンレス容器。',                           'owner_team' => '構造設計チーム',      'owner_avatar' => '🏗️', 'specs' => ['material' => 'SS316LN', 'pressure' => '1e-6 Pa', 'wallThickness' => '60 mm']],
            ['code' => 'blanket', 'name' => 'ブランケット',   'name_en' => 'Breeding Blanket',     'category' => 'thermal',   'description' => 'トリチウム増殖と熱回収を担うブランケットモジュール。',                 'owner_team' => '熱流体チーム',        'owner_avatar' => '🔥', 'specs' => ['material' => 'Li2TiO3/Be12Ti', 'coolant' => 'He gas', 'breedingRatio' => '1.15']],
            ['code' => 'divertor','name' => 'ダイバータ',     'name_en' => 'Divertor',             'category' => 'thermal',   'description' => '排熱・不純物排気を担うプラズマ対向機器。',                             'owner_team' => '熱流体チーム',        'owner_avatar' => '🔥', 'specs' => ['material' => 'W (Tungsten)', 'heatFlux' => '10 MW/m2', 'coolant' => 'Water']],
            ['code' => 'heating', 'name' => '加熱システム',   'name_en' => 'Heating System',       'category' => 'plasma',    'description' => 'NBI・ECRH等によるプラズマ加熱・電流駆動。',                           'owner_team' => 'プラズマ制御チーム',  'owner_avatar' => '⚡', 'specs' => ['nbiPower' => '33 MW', 'ecrhFreq' => '170 GHz', 'totalPower' => '50 MW']],
            ['code' => 'cryostat','name' => 'クライオスタット','name_en' => 'Cryostat',             'category' => 'structure', 'description' => '超伝導コイル冷却用の真空断熱容器。',                                   'owner_team' => '構造設計チーム',      'owner_avatar' => '🏗️', 'specs' => ['material' => 'SS304L', 'temperature' => '4.5 K', 'volume' => '16000 m3']],
            ['code' => 'fueling', 'name' => '燃料供給系',     'name_en' => 'Fueling System',       'category' => 'plasma',    'description' => 'ペレット入射・ガスパフによるD-T燃料供給。',                           'owner_team' => 'プラズマ制御チーム',  'owner_avatar' => '⚡', 'specs' => ['pelletSpeed' => '300 m/s', 'fuel' => 'D-T', 'method' => 'Pellet Injection']],
        ];

        $compModels = [];
        foreach ($components as $data) {
            $compModels[$data['code']] = Component::create($data);
        }

        $deps = [
            ['from' => 'tfc',     'to' => 'vv',       'relation' => '磁場荷重が容器構造に影響',             'strength' => 'high'],
            ['from' => 'tfc',     'to' => 'cryostat', 'relation' => '冷却要件がクライオスタット設計を規定', 'strength' => 'high'],
            ['from' => 'tfc',     'to' => 'blanket',  'relation' => '磁場配置がブランケット空間を制約',     'strength' => 'medium'],
            ['from' => 'pfc',     'to' => 'vv',       'relation' => '電磁力が容器支持構造に影響',           'strength' => 'medium'],
            ['from' => 'pfc',     'to' => 'heating',  'relation' => '磁場形状が加熱ポート配置を制約',       'strength' => 'low'],
            ['from' => 'pfc',     'to' => 'cryostat', 'relation' => '冷却系統を共有',                       'strength' => 'medium'],
            ['from' => 'vv',      'to' => 'blanket',  'relation' => '容器内壁形状がモジュール設計を規定',   'strength' => 'high'],
            ['from' => 'vv',      'to' => 'divertor', 'relation' => '容器下部形状がダイバータ配置を決定',   'strength' => 'high'],
            ['from' => 'vv',      'to' => 'heating',  'relation' => 'ポート位置・サイズが加熱系を制約',     'strength' => 'medium'],
            ['from' => 'vv',      'to' => 'fueling',  'relation' => 'ポート位置が燃料供給配置を制約',       'strength' => 'low'],
            ['from' => 'blanket', 'to' => 'divertor', 'relation' => '熱負荷分担の設計連携',                 'strength' => 'medium'],
            ['from' => 'blanket', 'to' => 'fueling',  'relation' => 'トリチウム増殖が燃料サイクルに影響',   'strength' => 'medium'],
            ['from' => 'heating', 'to' => 'divertor', 'relation' => '加熱パワーが排熱設計に直結',           'strength' => 'high'],
            ['from' => 'divertor','to' => 'fueling',  'relation' => '不純物排気が燃料純度に影響',           'strength' => 'low'],
        ];

        foreach ($deps as $d) {
            Dependency::create([
                'from_component_id' => $compModels[$d['from']]->id,
                'to_component_id'   => $compModels[$d['to']]->id,
                'relation'          => $d['relation'],
                'strength'          => $d['strength'],
            ]);
        }

        $docs = [
            ['name' => 'TFコイル設計仕様書 v3.2',                 'doc_type' => '仕様書',       'components' => ['tfc']],
            ['name' => '真空容器構造解析レポート',                 'doc_type' => '解析レポート', 'components' => ['vv', 'tfc']],
            ['name' => 'ブランケットモジュール熱設計書',           'doc_type' => '設計書',       'components' => ['blanket', 'vv']],
            ['name' => 'ダイバータ熱負荷評価書',                   'doc_type' => '評価書',       'components' => ['divertor', 'blanket', 'heating']],
            ['name' => 'クライオスタット冷却系統図',               'doc_type' => '系統図',       'components' => ['cryostat', 'tfc', 'pfc']],
            ['name' => '加熱・電流駆動システム概念設計',           'doc_type' => '設計書',       'components' => ['heating', 'pfc']],
            ['name' => '燃料サイクルフロー図',                     'doc_type' => '系統図',       'components' => ['fueling', 'blanket', 'divertor']],
            ['name' => '安全評価報告書（予備審査用）',             'doc_type' => '規制文書',     'components' => ['vv', 'blanket', 'divertor', 'fueling']],
            ['name' => '電磁力解析サマリー',                       'doc_type' => '解析レポート', 'components' => ['tfc', 'pfc', 'vv']],
            ['name' => '全体配置図 Rev.7',                         'doc_type' => '図面',         'components' => ['tfc', 'pfc', 'vv', 'blanket', 'divertor', 'cryostat']],
        ];

        foreach ($docs as $d) {
            $doc = Document::create([
                'name'     => $d['name'],
                'doc_type' => $d['doc_type'],
            ]);
            $compIds = array_map(fn($code) => $compModels[$code]->id, $d['components']);
            $doc->components()->attach($compIds);
        }
    }
}