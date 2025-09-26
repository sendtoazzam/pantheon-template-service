<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
            'type' => $this->type,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'read_at' => $this->read_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'priority' => $this->priority,
            'status' => $this->status,
            'is_urgent' => $this->is_urgent,
            'is_actionable' => $this->is_actionable,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Additional computed fields
            'is_read' => $this->read_at !== null,
            'is_unread' => $this->read_at === null,
            'time_ago' => $this->getTimeAgo(),
            'type_icon' => $this->getTypeIcon(),
            'priority_color' => $this->getPriorityColor(),
            'formatted_priority' => $this->getFormattedPriority(),
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
     * Get icon for notification type
     */
    private function getTypeIcon(): string
    {
        return match($this->type) {
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'times-circle',
            default => 'bell',
        };
    }

    /**
     * Get color for priority level
     */
    private function getPriorityColor(): string
    {
        return match($this->priority) {
            'low' => 'gray',
            'normal' => 'blue',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'blue',
        };
    }

    /**
     * Get formatted priority label
     */
    private function getFormattedPriority(): string
    {
        return match($this->priority) {
            'low' => 'Low Priority',
            'normal' => 'Normal Priority',
            'high' => 'High Priority',
            'urgent' => 'Urgent',
            default => ucfirst($this->priority),
        };
    }
}
