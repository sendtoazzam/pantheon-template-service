<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'middle_name',
        'display_name',
        'date_of_birth',
        'gender',
        'nationality',
        'ethnicity',
        'religion',
        'marital_status',
        'primary_phone',
        'secondary_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'home_address',
        'home_city',
        'home_state',
        'home_country',
        'home_postal_code',
        'home_latitude',
        'home_longitude',
        'work_address',
        'work_city',
        'work_state',
        'work_country',
        'work_postal_code',
        'work_latitude',
        'work_longitude',
        'occupation',
        'job_title',
        'company_name',
        'industry',
        'work_experience',
        'skills',
        'languages',
        'bio',
        'interests',
        'hobbies',
        'highest_education',
        'university',
        'degree',
        'field_of_study',
        'graduation_year',
        'certifications',
        'website',
        'linkedin_url',
        'twitter_handle',
        'facebook_url',
        'instagram_handle',
        'youtube_channel',
        'social_media_links',
        'blood_type',
        'medical_conditions',
        'allergies',
        'medications',
        'emergency_medical_contact',
        'insurance_provider',
        'insurance_policy_number',
        'preferred_language',
        'timezone',
        'currency',
        'dark_mode_enabled',
        'notification_preferences',
        'privacy_settings',
        'communication_preferences',
        'dietary_preferences',
        'dietary_restrictions',
        'smoking_status',
        'drinking_status',
        'fitness_level',
        'sports_interests',
        'entertainment_preferences',
        'travel_preferences',
        'annual_income_range',
        'employment_status',
        'credit_score_range',
        'financial_goals',
        'investment_interests',
        'number_of_children',
        'children_ages',
        'spouse_name',
        'spouse_occupation',
        'family_members',
        'custom_fields',
        'tags',
        'notes',
        'identity_verified',
        'identity_verified_at',
        'verification_document_type',
        'verification_document_number',
        'trust_score',
        'verification_status',
        'profile_views',
        'profile_completeness',
        'last_profile_update',
        'last_activity',
        'activity_summary',
    ];

    protected $casts = [
        'skills' => 'array',
        'languages' => 'array',
        'hobbies' => 'array',
        'certifications' => 'array',
        'social_media_links' => 'array',
        'notification_preferences' => 'array',
        'privacy_settings' => 'array',
        'communication_preferences' => 'array',
        'dietary_preferences' => 'array',
        'dietary_restrictions' => 'array',
        'fitness_level' => 'array',
        'sports_interests' => 'array',
        'entertainment_preferences' => 'array',
        'travel_preferences' => 'array',
        'financial_goals' => 'array',
        'investment_interests' => 'array',
        'children_ages' => 'array',
        'family_members' => 'array',
        'custom_fields' => 'array',
        'tags' => 'array',
        'verification_status' => 'array',
        'activity_summary' => 'array',
        'date_of_birth' => 'date',
        'graduation_year' => 'integer',
        'dark_mode_enabled' => 'boolean',
        'identity_verified' => 'boolean',
        'identity_verified_at' => 'datetime',
        'last_profile_update' => 'datetime',
        'last_activity' => 'datetime',
        'home_latitude' => 'decimal:8',
        'home_longitude' => 'decimal:11',
        'work_latitude' => 'decimal:8',
        'work_longitude' => 'decimal:11',
        'profile_views' => 'integer',
        'profile_completeness' => 'integer',
        'trust_score' => 'integer',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full name
     */
    public function getFullNameAttribute()
    {
        $name = trim($this->first_name . ' ' . $this->last_name);
        return $name ?: $this->display_name;
    }

    /**
     * Get the display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->display_name ?: $this->getFullNameAttribute();
    }

    /**
     * Get the age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the home address as a single string
     */
    public function getFullHomeAddressAttribute()
    {
        $parts = array_filter([
            $this->home_address,
            $this->home_city,
            $this->home_state,
            $this->home_country,
            $this->home_postal_code,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the work address as a single string
     */
    public function getFullWorkAddressAttribute()
    {
        $parts = array_filter([
            $this->work_address,
            $this->work_city,
            $this->work_state,
            $this->work_country,
            $this->work_postal_code,
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get the primary contact information
     */
    public function getPrimaryContactAttribute()
    {
        return [
            'phone' => $this->primary_phone,
            'email' => $this->user->email ?? null,
            'address' => $this->getFullHomeAddressAttribute(),
        ];
    }

    /**
     * Get the emergency contact information
     */
    public function getEmergencyContactAttribute()
    {
        return [
            'name' => $this->emergency_contact_name,
            'phone' => $this->emergency_contact_phone,
            'relationship' => $this->emergency_contact_relationship,
        ];
    }

    /**
     * Get the social media links as an array
     */
    public function getSocialMediaLinksAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Get the skills as an array
     */
    public function getSkillsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Get the languages as an array
     */
    public function getLanguagesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Get the hobbies as an array
     */
    public function getHobbiesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * Check if profile is complete
     */
    public function isComplete(): bool
    {
        return $this->profile_completeness >= 80;
    }

    /**
     * Check if identity is verified
     */
    public function isIdentityVerified(): bool
    {
        return $this->identity_verified;
    }

    /**
     * Check if profile is public
     */
    public function isPublic(): bool
    {
        return $this->privacy_settings['profile_public'] ?? false;
    }

    /**
     * Get the profile completeness percentage
     */
    public function getCompletenessPercentage(): int
    {
        return $this->profile_completeness;
    }

    /**
     * Calculate profile completeness
     */
    public function calculateCompleteness(): int
    {
        $fields = [
            'first_name', 'last_name', 'date_of_birth', 'gender', 'nationality',
            'primary_phone', 'home_address', 'home_city', 'home_country',
            'occupation', 'bio', 'interests', 'highest_education',
        ];

        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }

        return round(($completed / count($fields)) * 100);
    }

    /**
     * Update profile completeness
     */
    public function updateCompleteness(): void
    {
        $this->profile_completeness = $this->calculateCompleteness();
        $this->save();
    }

    /**
     * Get the trust score level
     */
    public function getTrustScoreLevel(): string
    {
        if ($this->trust_score >= 80) {
            return 'high';
        } elseif ($this->trust_score >= 60) {
            return 'medium';
        } elseif ($this->trust_score >= 40) {
            return 'low';
        } else {
            return 'very_low';
        }
    }

    /**
     * Get the trust score badge class
     */
    public function getTrustScoreBadgeClassAttribute()
    {
        return match($this->getTrustScoreLevel()) {
            'high' => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'low' => 'bg-orange-100 text-orange-800',
            'very_low' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope for verified profiles
     */
    public function scopeVerified($query)
    {
        return $query->where('identity_verified', true);
    }

    /**
     * Scope for public profiles
     */
    public function scopePublic($query)
    {
        return $query->whereJsonContains('privacy_settings->profile_public', true);
    }

    /**
     * Scope for profiles by completeness
     */
    public function scopeByCompleteness($query, $minCompleteness)
    {
        return $query->where('profile_completeness', '>=', $minCompleteness);
    }

    /**
     * Scope for profiles by trust score
     */
    public function scopeByTrustScore($query, $minTrustScore)
    {
        return $query->where('trust_score', '>=', $minTrustScore);
    }
}