<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
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
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'description' => $this->description,
            'website' => $this->website,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'verification_date' => $this->verification_date?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'settings' => $this->whenLoaded('settings'),
            
            // Additional computed fields
            'full_address' => $this->getFullAddress(),
            'is_active' => $this->status === 'active',
            'verification_status' => $this->getVerificationStatus(),
        ];
    }

    /**
     * Get the full address as a single string
     */
    private function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get verification status description
     */
    private function getVerificationStatus(): string
    {
        if (!$this->is_verified) {
            return 'unverified';
        }

        if ($this->verification_date) {
            return 'verified';
        }

        return 'pending';
    }
}
