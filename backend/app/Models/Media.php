<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'path',
        'alt_text',
        'is_hero',
        'is_placeholder',
    ];

    protected function casts(): array
    {
        return [
            'is_hero' => 'boolean',
            'is_placeholder' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
