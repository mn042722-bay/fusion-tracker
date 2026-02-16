<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Document extends Model
{
    protected $fillable = [
        'name', 'doc_type', 'file_path', 'version', 'status',
    ];

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Component::class, 'component_document');
    }
}