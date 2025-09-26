<?php

namespace App\Services;

use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class SecureTokenService
{
    /**
     * Generate a secure token with custom length
     */
    public static function generateSecureToken(int $length = 64): string
    {
        // Generate a cryptographically secure random string
        return Str::random($length);
    }

    /**
     * Generate a JWT-like token (longer format)
     */
    public static function generateJWTLikeToken(): string
    {
        // Generate a 128-character token similar to JWT format
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode([
            'iss' => config('app.name'),
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days
            'sub' => 'api_token'
        ]));
        $signature = Str::random(64);
        
        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Create a personal access token with custom token
     */
    public static function createTokenWithCustomValue($user, string $name = 'auth-token', array $abilities = ['*']): PersonalAccessToken
    {
        $token = self::generateSecureToken(64);
        
        return $user->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $token),
            'abilities' => $abilities,
        ]);
    }

    /**
     * Generate a UUID-based token
     */
    public static function generateUUIDToken(): string
    {
        return Str::uuid()->toString() . '.' . Str::random(32);
    }

    /**
     * Generate a token with prefix
     */
    public static function generatePrefixedToken(string $prefix = 'pantheon'): string
    {
        return $prefix . '_' . Str::random(48);
    }
}
