<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiCallLogResource extends JsonResource
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
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'username' => $this->user->username,
                ];
            }),
            'method' => $this->method,
            'url' => $this->url,
            'endpoint' => $this->endpoint,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'request_headers' => $this->request_headers,
            'request_body' => $this->request_body,
            'request_params' => $this->request_params,
            'response_status' => $this->response_status,
            'response_headers' => $this->response_headers,
            'response_body' => $this->response_body,
            'response_size_bytes' => $this->response_size_bytes,
            'response_size_human' => $this->response_size_human,
            'execution_time_ms' => $this->execution_time_ms,
            'memory_usage_bytes' => $this->memory_usage_bytes,
            'memory_usage_human' => $this->memory_usage_human,
            'peak_memory_bytes' => $this->peak_memory_bytes,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'metadata' => $this->metadata,
            'called_at' => $this->called_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
