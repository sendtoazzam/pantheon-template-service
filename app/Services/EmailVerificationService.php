<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class EmailVerificationService
{
    /**
     * Send verification email
     */
    public function sendVerificationEmail(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return ['success' => false, 'message' => 'Email already verified'];
        }

        $token = Str::random(64);
        
        // Store token in cache for 24 hours
        Cache::put("email_verification:{$token}", $user->id, 86400);

        // Send verification email
        Mail::send('emails.verify-email', [
            'user' => $user,
            'token' => $token,
            'url' => config('app.frontend_url') . '/verify-email?token=' . $token
        ], function ($message) use ($user) {
            $message->to($user->email)
                   ->subject('Verify Your Email Address');
        });

        return ['success' => true, 'message' => 'Verification email sent'];
    }

    /**
     * Verify email with token
     */
    public function verifyEmail(string $token)
    {
        $userId = Cache::get("email_verification:{$token}");
        
        if (!$userId) {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }

        $user = User::find($userId);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if ($user->hasVerifiedEmail()) {
            return ['success' => false, 'message' => 'Email already verified'];
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => now()
        ]);

        // Clear token
        Cache::forget("email_verification:{$token}");

        return ['success' => true, 'message' => 'Email verified successfully'];
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(User $user)
    {
        // Rate limiting - max 3 attempts per hour
        $key = "email_verification_resend:{$user->id}";
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= 3) {
            return ['success' => false, 'message' => 'Too many verification attempts. Please try again later.'];
        }

        Cache::put($key, $attempts + 1, 3600); // 1 hour

        return $this->sendVerificationEmail($user);
    }
}
