<?php

namespace App\Models;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'type',
        'label',
        'path_or_url',
        'status',
        'raw_text',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'type' => SourceType::class,
            'status' => SourceStatus::class,
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }
}
