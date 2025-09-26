<?php

namespace App\Http\Guards;

use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class SecureTokenGuard extends TokenGuard
{
    /**
     * Create a new authentication guard.
     *
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $inputKey
     * @param  string  $storageKey
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request, $inputKey = 'api_token', $storageKey = 'api_token')
    {
        parent::__construct($provider, $request, $inputKey, $storageKey);
    }

    /**
     * Get the token for the current request.
     *
     * @return string|null
     */
    public function getTokenForRequest()
    {
        $token = parent::getTokenForRequest();
        
        if (!$token) {
            return null;
        }

        // If token doesn't contain a pipe, it's our custom format
        if (strpos($token, '|') === false) {
            return $token;
        }

        // Extract the actual token part (after the pipe)
        $parts = explode('|', $token, 2);
        return $parts[1] ?? $token;
    }
}
