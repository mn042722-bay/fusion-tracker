<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    protected $fillable = [
        'code', 'name', 'name_en', 'category',
        'description', 'owner_team', 'owner_avatar', 'specs',
    ];

    protected $casts = [
        'specs' => 'array',
    ];

    public function dependsOn(): HasMany
    {
        return $this->hasMany(Dependency::class, 'from_component_id');
    }

    public function dependedBy(): HasMany
    {
        return $this->hasMany(Dependency::class, 'to_component_id');
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'component_document');
    }

    public function designChanges(): HasMany
    {
        return $this->hasMany(DesignChange::class);
    }

    public function getImpactedComponents(): array
    {
        $direct = $this->dependsOn()->with('toComponent')->get()->map(function ($dep) {
            return [
                'id'       => $dep->toComponent->id,
                'code'     => $dep->toComponent->code,
                'name'     => $dep->toComponent->name,
                'relation' => $dep->relation,
                'strength' => $dep->strength,
                'depth'    => 1,
            ];
        })->toArray();

        $directIds = array_column($direct, 'id');

        $indirect = [];
        foreach ($directIds as $directId) {
            $secondLevel = Dependency::where('from_component_id', $directId)
                ->where('to_component_id', '!=', $this->id)
                ->whereNotIn('to_component_id', $directIds)
                ->with('toComponent')
                ->get();

            foreach ($secondLevel as $dep) {
                $alreadyAdded = array_column($indirect, 'id');
                if (!in_array($dep->to_component_id, $alreadyAdded)) {
                    $viaName = Component::find($directId)->name;
                    $indirect[] = [
                        'id'       => $dep->toComponent->id,
                        'code'     => $dep->toComponent->code,
                        'name'     => $dep->toComponent->name,
                        'relation' => "{$viaName}経由: {$dep->relation}",
                        'strength' => 'low',
                        'depth'    => 2,
                    ];
                }
            }
        }

        return array_merge($direct, $indirect);
    }
}