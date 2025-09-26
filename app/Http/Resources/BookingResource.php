<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'merchant_id' => $this->merchant_id,
            'service_name' => $this->service_name,
            'service_description' => $this->service_description,
            'booking_date' => $this->booking_date,
            'booking_time' => $this->booking_time,
            'duration_minutes' => $this->duration_minutes,
            'price' => $this->price,
            'currency' => $this->currency,
            'status' => $this->status,
            'notes' => $this->notes,
            'special_requests' => $this->special_requests,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            
            // Additional computed fields
            'formatted_price' => $this->getFormattedPrice(),
            'end_time' => $this->getEndTime(),
            'booking_datetime' => $this->getBookingDateTime(),
            'is_past' => $this->isPast(),
            'is_today' => $this->isToday(),
            'status_label' => $this->getStatusLabel(),
            'duration_hours' => $this->getDurationHours(),
        ];
    }

    /**
     * Get formatted price with currency
     */
    private function getFormattedPrice(): string
    {
        return number_format($this->price, 2) . ' ' . strtoupper($this->currency);
    }

    /**
     * Get end time of the booking
     */
    private function getEndTime(): string
    {
        return date('H:i:s', strtotime($this->booking_time . ' + ' . $this->duration_minutes . ' minutes'));
    }

    /**
     * Get combined booking date and time
     */
    private function getBookingDateTime(): string
    {
        return $this->booking_date . ' ' . $this->booking_time;
    }

    /**
     * Check if booking is in the past
     */
    private function isPast(): bool
    {
        $bookingDateTime = $this->booking_date . ' ' . $this->booking_time;
        return strtotime($bookingDateTime) < time();
    }

    /**
     * Check if booking is today
     */
    private function isToday(): bool
    {
        return $this->booking_date === date('Y-m-d');
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending Confirmation',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'no_show' => 'No Show',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get duration in hours and minutes
     */
    private function getDurationHours(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }
}
