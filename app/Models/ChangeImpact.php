<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeImpact extends Model
{
    protected $fillable = [
        'design_change_id', 'component_id', 'strength', 'relation', 'depth',
    ];

    public function designChange(): BelongsTo
    {
        return $this->belongsTo(DesignChange::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }
}