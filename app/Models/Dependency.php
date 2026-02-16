<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dependency extends Model
{
    protected $fillable = [
        'from_component_id', 'to_component_id', 'relation', 'strength',
    ];

    public function fromComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'from_component_id');
    }

    public function toComponent(): BelongsTo
    {
        return $this->belongsTo(Component::class, 'to_component_id');
    }
}