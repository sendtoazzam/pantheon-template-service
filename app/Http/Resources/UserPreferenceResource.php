<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'category' => $this->category,
            'key' => $this->key,
            'value' => $this->value,
            'data_type' => $this->data_type,
            'is_public' => $this->is_public,
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Additional computed fields
            'parsed_value' => $this->getParsedValue(),
            'formatted_value' => $this->getFormattedValue(),
            'category_label' => $this->getCategoryLabel(),
            'data_type_label' => $this->getDataTypeLabel(),
        ];
    }

    /**
     * Get parsed value based on data type
     */
    private function getParsedValue(): mixed
    {
        return match($this->data_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            'float' => (float) $this->value,
            'array' => json_decode($this->value, true),
            'object' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get formatted value for display
     */
    private function getFormattedValue(): string
    {
        return match($this->data_type) {
            'boolean' => $this->getParsedValue() ? 'Yes' : 'No',
            'integer' => number_format($this->getParsedValue()),
            'float' => number_format($this->getParsedValue(), 2),
            'array', 'object' => json_encode($this->getParsedValue(), JSON_PRETTY_PRINT),
            default => $this->value,
        };
    }

    /**
     * Get human-readable category label
     */
    private function getCategoryLabel(): string
    {
        return match($this->category) {
            'notifications' => 'Notifications',
            'privacy' => 'Privacy',
            'appearance' => 'Appearance',
            'language' => 'Language',
            'security' => 'Security',
            'general' => 'General',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get human-readable data type label
     */
    private function getDataTypeLabel(): string
    {
        return match($this->data_type) {
            'boolean' => 'True/False',
            'string' => 'Text',
            'integer' => 'Number',
            'float' => 'Decimal',
            'array' => 'List',
            'object' => 'Object',
            default => ucfirst($this->data_type),
        };
    }
}
