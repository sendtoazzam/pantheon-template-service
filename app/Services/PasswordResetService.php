<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class PasswordResetService
{
    /**
     * Send password reset link
     */
    public function sendResetLink(string $email)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Rate limiting - max 3 attempts per hour
        $key = "password_reset:{$email}";
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 3) {
            return ['success' => false, 'message' => 'Too many reset attempts. Please try again later.'];
        }

        Cache::put($key, $attempts + 1, 3600); // 1 hour

        // Generate token
        $token = Str::random(64);
        
        // Store token in cache for 1 hour
        Cache::put("password_reset:{$token}", [
            'user_id' => $user->id,
            'email' => $email
        ], 3600);

        // Send reset email
        Mail::send('emails.password-reset', [
            'user' => $user,
            'token' => $token,
            'url' => config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($email)
        ], function ($message) use ($user) {
            $message->to($user->email)
                   ->subject('Reset Your Password');
        });

        return ['success' => true, 'message' => 'Password reset email sent'];
    }

    /**
     * Reset password with token
     */
    public function resetPassword(string $email, string $password, string $token)
    {
        $resetData = Cache::get("password_reset:{$token}");
        
        if (!$resetData) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }

        if ($resetData['email'] !== $email) {
            return ['success' => false, 'message' => 'Invalid token for this email'];
        }

        $user = User::find($resetData['user_id']);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Update password
        $user->update([
            'password' => Hash::make($password)
        ]);

        // Clear token
        Cache::forget("password_reset:{$token}");

        // Fire password reset event
        event(new PasswordReset($user));

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password)
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if password was recently used
     */
    public function isPasswordRecentlyUsed(User $user, string $password)
    {
        // Check last 5 passwords (stored in user's password history)
        $passwordHistory = json_decode($user->password_history ?? '[]', true);
        
        foreach ($passwordHistory as $oldPassword) {
            if (Hash::check($password, $oldPassword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update password history
     */
    public function updatePasswordHistory(User $user, string $newPassword)
    {
        $passwordHistory = json_decode($user->password_history ?? '[]', true);
        
        // Add current password to history
        array_unshift($passwordHistory, $user->password);
        
        // Keep only last 5 passwords
        $passwordHistory = array_slice($passwordHistory, 0, 5);
        
        $user->update([
            'password_history' => json_encode($passwordHistory)
        ]);
    }
}
