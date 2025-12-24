<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceFlyer extends BaseModel
{
    protected $table = 'service_flyers';

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'image_url',
        'image_public_id',
        'target_audience',
        'is_active',
        'sort_order',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the admin user who created this flyer
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uuid');
    }

    /**
     * Scope to get active flyers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get flyers for specific audience
     */
    public function scopeForAudience($query, $audience)
    {
        return $query->where(function($q) use ($audience) {
            $q->where('target_audience', $audience)
              ->orWhere('target_audience', 'both');
        });
    }

    /**
     * Scope to order flyers
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
    }
}