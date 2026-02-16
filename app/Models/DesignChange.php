<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignChange extends Model
{
    protected $fillable = [
        'change_code', 'component_id', 'title', 'description',
        'author', 'status', 'ai_summary', 'ai_raw_response', 'analyzed_at',
    ];

    protected $casts = [
        'ai_raw_response' => 'array',
        'analyzed_at'     => 'datetime',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function impacts(): HasMany
    {
        return $this->hasMany(ChangeImpact::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(ChangeNotification::class);
    }

    public static function nextCode(): string
    {
        $last = static::orderByDesc('id')->first();
        $num = $last ? intval(substr($last->change_code, 4)) + 1 : 1;
        return 'CHG-' . str_pad($num, 3, '0', STR_PAD_LEFT);
    }
}