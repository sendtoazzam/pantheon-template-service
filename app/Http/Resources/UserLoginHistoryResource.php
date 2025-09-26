<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLoginHistoryResource extends JsonResource
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
            'login_method' => $this->login_method,
            'ip_address' => $this->ip_address,
            'device_type' => $this->device_type,
            'browser' => $this->browser,
            'os' => $this->os,
            'country' => $this->country,
            'city' => $this->city,
            'is_successful' => $this->is_successful,
            'failure_reason' => $this->failure_reason,
            'login_at' => $this->login_at,
            'logout_at' => $this->logout_at,
            'session_duration_minutes' => $this->session_duration_minutes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
