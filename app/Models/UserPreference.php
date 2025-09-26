<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'key',
        'value',
        'data_type',
        'is_public',
        'is_encrypted',
        'is_sensitive',
        'description',
        'default_value',
        'validation_rules',
        'options',
        'group',
        'order',
        'is_required',
        'is_readonly',
        'is_hidden',
        'depends_on',
        'triggers',
        'metadata',
        'tags',
        'version',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'value' => 'array',
        'default_value' => 'array',
        'validation_rules' => 'array',
        'options' => 'array',
        'depends_on' => 'array',
        'triggers' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'is_sensitive' => 'boolean',
        'is_required' => 'boolean',
        'is_readonly' => 'boolean',
        'is_hidden' => 'boolean',
        'order' => 'integer',
        'version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the actual value based on data type
     */
    public function getActualValueAttribute()
    {
        if ($this->is_encrypted) {
            return decrypt($this->value);
        }

        return match($this->data_type) {
            'boolean' => (bool) $this->value,
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'string' => (string) $this->value,
            'array' => is_array($this->value) ? $this->value : json_decode($this->value, true),
            'object' => is_object($this->value) ? $this->value : json_decode($this->value, true),
            'json' => is_array($this->value) ? $this->value : json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Set the value with proper data type conversion
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted) {
            $this->attributes['value'] = encrypt($value);
        } else {
            $this->attributes['value'] = match($this->data_type) {
                'boolean' => (bool) $value,
                'integer' => (int) $value,
                'float' => (float) $value,
                'string' => (string) $value,
                'array', 'object', 'json' => is_array($value) ? $value : json_decode($value, true),
                default => $value,
            };
        }
    }

    /**
     * Check if preference is public
     */
    public function isPublic(): bool
    {
        return $this->is_public;
    }

    /**
     * Check if preference is encrypted
     */
    public function isEncrypted(): bool
    {
        return $this->is_encrypted;
    }

    /**
     * Check if preference is sensitive
     */
    public function isSensitive(): bool
    {
        return $this->is_sensitive;
    }

    /**
     * Check if preference is required
     */
    public function isRequired(): bool
    {
        return $this->is_required;
    }

    /**
     * Check if preference is readonly
     */
    public function isReadonly(): bool
    {
        return $this->is_readonly;
    }

    /**
     * Check if preference is hidden
     */
    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    /**
     * Get the default value
     */
    public function getDefaultValue()
    {
        return $this->default_value ?? $this->getDefaultValueForType();
    }

    /**
     * Get default value based on data type
     */
    private function getDefaultValueForType()
    {
        return match($this->data_type) {
            'boolean' => false,
            'integer' => 0,
            'float' => 0.0,
            'string' => '',
            'array' => [],
            'object' => (object) [],
            'json' => [],
            default => null,
        };
    }

    /**
     * Validate the value against validation rules
     */
    public function validateValue($value = null)
    {
        $value = $value ?? $this->value;
        $rules = $this->validation_rules ?? [];

        if (empty($rules)) {
            return true;
        }

        // Basic validation based on data type
        $typeValid = match($this->data_type) {
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'float' => is_float($value) || is_numeric($value),
            'string' => is_string($value),
            'array' => is_array($value),
            'object' => is_object($value) || is_array($value),
            'json' => is_array($value) || is_object($value),
            default => true,
        };

        if (!$typeValid) {
            return false;
        }

        // Additional validation rules
        foreach ($rules as $rule => $constraint) {
            if (!$this->validateRule($rule, $constraint, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a specific rule
     */
    private function validateRule($rule, $constraint, $value)
    {
        return match($rule) {
            'min' => $value >= $constraint,
            'max' => $value <= $constraint,
            'min_length' => strlen($value) >= $constraint,
            'max_length' => strlen($value) <= $constraint,
            'in' => in_array($value, $constraint),
            'not_in' => !in_array($value, $constraint),
            'regex' => preg_match($constraint, $value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'required' => !empty($value),
            default => true,
        };
    }

    /**
     * Get the display value for UI
     */
    public function getDisplayValueAttribute()
    {
        if ($this->is_sensitive && !$this->is_public) {
            return '••••••••';
        }

        return $this->actual_value;
    }

    /**
     * Get the input type for forms
     */
    public function getInputTypeAttribute()
    {
        return match($this->data_type) {
            'boolean' => 'checkbox',
            'integer' => 'number',
            'float' => 'number',
            'string' => 'text',
            'array' => 'select',
            'object' => 'textarea',
            'json' => 'textarea',
            default => 'text',
        };
    }

    /**
     * Get the input options for select/radio/checkbox
     */
    public function getInputOptionsAttribute()
    {
        if ($this->data_type === 'array' && !empty($this->options)) {
            return $this->options;
        }

        return [];
    }

    /**
     * Get category badge class
     */
    public function getCategoryBadgeClassAttribute()
    {
        return match($this->category) {
            'profile' => 'bg-blue-100 text-blue-800',
            'privacy' => 'bg-green-100 text-green-800',
            'notifications' => 'bg-yellow-100 text-yellow-800',
            'security' => 'bg-red-100 text-red-800',
            'appearance' => 'bg-purple-100 text-purple-800',
            'system' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get data type badge class
     */
    public function getDataTypeBadgeClassAttribute()
    {
        return match($this->data_type) {
            'boolean' => 'bg-green-100 text-green-800',
            'integer' => 'bg-blue-100 text-blue-800',
            'float' => 'bg-blue-100 text-blue-800',
            'string' => 'bg-yellow-100 text-yellow-800',
            'array' => 'bg-purple-100 text-purple-800',
            'object' => 'bg-purple-100 text-purple-800',
            'json' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Scope for preferences by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for public preferences
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for private preferences
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope for encrypted preferences
     */
    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope for sensitive preferences
     */
    public function scopeSensitive($query)
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Scope for required preferences
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for visible preferences
     */
    public function scopeVisible($query)
    {
        return $query->where('is_hidden', false);
    }

    /**
     * Scope for preferences by group
     */
    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope for preferences by data type
     */
    public function scopeByDataType($query, $dataType)
    {
        return $query->where('data_type', $dataType);
    }

    /**
     * Scope for preferences by tag
     */
    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    /**
     * Scope for ordered preferences
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('key');
    }
}