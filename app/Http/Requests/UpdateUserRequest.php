<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('superadmin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->route('user');
        
        return [
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|max:255|unique:users,username,' . $userId,
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $userId,
            'phone' => 'sometimes|nullable|string|max:255',
            'status' => 'sometimes|string|in:active,inactive,suspended',
            'is_admin' => 'sometimes|boolean',
            'is_vendor' => 'sometimes|boolean',
            'role' => 'sometimes|string|exists:roles,name'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'username.required' => 'The username field is required.',
            'username.unique' => 'The username has already been taken.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'role.exists' => 'The selected role is invalid.',
            'status.in' => 'The status must be one of: active, inactive, suspended.'
        ];
    }
}
