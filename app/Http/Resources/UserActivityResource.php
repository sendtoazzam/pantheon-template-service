<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserActivityResource extends JsonResource
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
            'activity_type' => $this->activity_type,
            'activity_category' => $this->activity_category,
            'description' => $this->description,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Additional computed fields
            'time_ago' => $this->getTimeAgo(),
            'status_label' => $this->getStatusLabel(),
            'category_label' => $this->getCategoryLabel(),
            'status_color' => $this->getStatusColor(),
            'is_recent' => $this->isRecent(),
            'formatted_date' => $this->getFormattedDate(),
        ];
    }

    /**
     * Get human-readable time ago
     */
    private function getTimeAgo(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'success' => 'Success',
            'failed' => 'Failed',
            'warning' => 'Warning',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get human-readable category label
     */
    private function getCategoryLabel(): string
    {
        return match($this->activity_category) {
            'authentication' => 'Authentication',
            'profile' => 'Profile',
            'booking' => 'Booking',
            'payment' => 'Payment',
            'system' => 'System',
            default => ucfirst($this->activity_category),
        };
    }

    /**
     * Get color for status
     */
    private function getStatusColor(): string
    {
        return match($this->status) {
            'success' => 'green',
            'failed' => 'red',
            'warning' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Check if activity is recent (within last hour)
     */
    private function isRecent(): bool
    {
        return $this->created_at->isAfter(now()->subHour());
    }

    /**
     * Get formatted date
     */
    private function getFormattedDate(): string
    {
        return $this->created_at->format('M j, Y g:i A');
    }
}
