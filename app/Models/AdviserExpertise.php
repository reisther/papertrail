<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdviserExpertise extends Model
{

    protected $table = 'adviser_expertise';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'adviser_id',
        'machine_learning',
        'ai_integration',
        'cybersecurity',
        'iot',
        'cloud_computing',
        'data_analytics',
        'web_development',
        'mobile_development',
        'database_systems',
        'networking',
        'custom_expertise',
    ];

    /**
     * Cast values properly to boolean
     */
    protected $casts = [
        'machine_learning' => 'boolean',
        'ai_integration' => 'boolean',
        'cybersecurity' => 'boolean',
        'iot' => 'boolean',
        'cloud_computing' => 'boolean',
        'data_analytics' => 'boolean',
        'web_development' => 'boolean',
        'mobile_development' => 'boolean',
        'database_systems' => 'boolean',
        'networking' => 'boolean',
        'custom_expertise' => 'array',
    ];

    /**
     * Each expertise belongs to one adviser (User)
     */
    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }
}
