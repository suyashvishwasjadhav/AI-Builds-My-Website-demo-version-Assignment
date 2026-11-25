<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'city',
        'slug',
        'custom_domain',
        'domain_verified_at',
        'status',
        'template',
        'theme_config',
        'language',
        'timezone',
        'whatsapp_enabled',
        'whatsapp_greeting',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'theme_config' => 'array',
            'whatsapp_enabled' => 'boolean',
            'domain_verified_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    public function facts(): HasMany
    {
        return $this->hasMany(Fact::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(SiteTranslation::class);
    }
}
