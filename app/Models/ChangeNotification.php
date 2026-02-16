<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeNotification extends Model
{
    protected $table = 'change_notifications';

    protected $fillable = [
        'design_change_id', 'component_id', 'team',
        'strength', 'message', 'status', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
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