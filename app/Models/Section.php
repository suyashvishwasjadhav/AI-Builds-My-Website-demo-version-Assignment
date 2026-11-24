<?php

namespace App\Models;

use App\Enums\SectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'type',
        'content',
        'is_visible',
        'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => SectionType::class,
            'content' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SectionVersion::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(SectionVersion::class, 'current_version_id');
    }
}
