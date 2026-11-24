<?php

namespace App\Models;

use App\Enums\SectionCreatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'content',
        'created_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'created_by' => SectionCreatedBy::class,
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
