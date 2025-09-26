<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
            'bio' => $this->bio,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'phone_secondary' => $this->phone_secondary,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            
            // Additional computed fields
            'full_address' => $this->getFullAddress(),
            'age' => $this->getAge(),
            'age_group' => $this->getAgeGroup(),
            'gender_label' => $this->getGenderLabel(),
            'is_complete' => $this->isComplete(),
            'completion_percentage' => $this->getCompletionPercentage(),
        ];
    }

    /**
     * Get the full address as a single string
     */
    private function getFullAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return empty($parts) ? null : implode(', ', $parts);
    }

    /**
     * Calculate age from date of birth
     */
    private function getAge(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return now()->diffInYears($this->date_of_birth);
    }

    /**
     * Get age group based on age
     */
    private function getAgeGroup(): ?string
    {
        $age = $this->getAge();
        
        if (!$age) {
            return null;
        }

        return match(true) {
            $age < 18 => 'Under 18',
            $age < 25 => '18-24',
            $age < 35 => '25-34',
            $age < 45 => '35-44',
            $age < 55 => '45-54',
            $age < 65 => '55-64',
            default => '65+',
        };
    }

    /**
     * Get human-readable gender label
     */
    private function getGenderLabel(): ?string
    {
        return match($this->gender) {
            'male' => 'Male',
            'female' => 'Female',
            'other' => 'Other',
            'prefer_not_to_say' => 'Prefer not to say',
            default => null,
        };
    }

    /**
     * Check if profile is complete (has all required fields)
     */
    private function isComplete(): bool
    {
        $requiredFields = [
            'bio', 'date_of_birth', 'gender', 'nationality',
            'address', 'city', 'state', 'country', 'postal_code',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship'
        ];

        foreach ($requiredFields as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get profile completion percentage
     */
    private function getCompletionPercentage(): int
    {
        $fields = [
            'bio', 'date_of_birth', 'gender', 'nationality',
            'address', 'city', 'state', 'country', 'postal_code',
            'phone_secondary', 'emergency_contact_name', 
            'emergency_contact_phone', 'emergency_contact_relationship'
        ];

        $filledFields = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filledFields++;
            }
        }

        return round(($filledFields / count($fields)) * 100);
    }
}
