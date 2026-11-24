<?php

namespace App\Models;

use App\Enums\FactConfidence;
use App\Enums\FactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fact extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'source_id',
        'key',
        'value',
        'snippet',
        'status',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'status' => FactStatus::class,
            'confidence' => FactConfidence::class,
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
