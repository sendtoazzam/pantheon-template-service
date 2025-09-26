<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Here you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | here which uses session storage and the Eloquent user provider.
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | Supported: "session", "token"
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        'superadmin' => [
            'driver' => 'session',
            'provider' => 'superadmins',
        ],

        'api_superadmin' => [
            'driver' => 'sanctum',
            'provider' => 'superadmins',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        'api_admin' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],

        'vendor' => [
            'driver' => 'session',
            'provider' => 'vendors',
        ],

        'api_vendor' => [
            'driver' => 'sanctum',
            'provider' => 'vendors',
        ],

        'jwt' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication drivers have a user provider. This defines how the
    | users are actually retrieved out of your database or other storage
    | mechanisms used by this application to persist your user's data.
    |
    | If you have multiple user tables or models you may configure multiple
    | sources which represent each model / table. These sources may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'superadmins' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'conditions' => [
                'status' => 'active',
                'is_admin' => true,
            ],
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'conditions' => [
                'status' => 'active',
                'is_admin' => true,
            ],
        ],

        'vendors' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
            'conditions' => [
                'status' => 'active',
                'is_vendor' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 30,
            'throttle' => 120,
        ],

        'vendors' => [
            'provider' => 'vendors',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

    /*
    |--------------------------------------------------------------------------
    | Guard Security Settings
    |--------------------------------------------------------------------------
    |
    | Additional security settings for different guards
    |
    */

    'security' => [
        'web' => [
            'session_lifetime' => 120, // minutes
            'remember_me_lifetime' => 20160, // minutes (2 weeks)
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutes
            'require_2fa' => false,
        ],

        'api' => [
            'token_lifetime' => 525600, // minutes (1 year)
            'refresh_token_lifetime' => 10080, // minutes (1 week)
            'max_tokens_per_user' => 10,
            'require_2fa' => false,
            'rate_limit' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
            ],
        ],

        'superadmin' => [
            'session_lifetime' => 30, // minutes (30 minutes)
            'remember_me_lifetime' => 1440, // minutes (1 day)
            'max_login_attempts' => 2,
            'lockout_duration' => 60, // minutes (1 hour)
            'require_2fa' => true,
            'ip_whitelist' => [], // Add IP addresses for superadmin access
            'require_strong_password' => true,
            'session_timeout_warning' => 5, // minutes before timeout warning
        ],

        'api_superadmin' => [
            'token_lifetime' => 480, // minutes (8 hours)
            'refresh_token_lifetime' => 1440, // minutes (1 day)
            'max_tokens_per_user' => 3,
            'require_2fa' => true,
            'rate_limit' => [
                'max_attempts' => 2000,
                'decay_minutes' => 1,
            ],
            'require_strong_password' => true,
            'audit_all_actions' => true,
        ],

        'admin' => [
            'session_lifetime' => 60, // minutes
            'remember_me_lifetime' => 10080, // minutes (1 week)
            'max_login_attempts' => 3,
            'lockout_duration' => 30, // minutes
            'require_2fa' => true,
            'ip_whitelist' => [], // Add IP addresses for admin access
            'require_strong_password' => true,
        ],

        'api_admin' => [
            'token_lifetime' => 1440, // minutes (1 day)
            'refresh_token_lifetime' => 10080, // minutes (1 week)
            'max_tokens_per_user' => 5,
            'require_2fa' => true,
            'rate_limit' => [
                'max_attempts' => 1000,
                'decay_minutes' => 1,
            ],
            'require_strong_password' => true,
        ],

        'vendor' => [
            'session_lifetime' => 480, // minutes (8 hours)
            'remember_me_lifetime' => 20160, // minutes (2 weeks)
            'max_login_attempts' => 5,
            'lockout_duration' => 15, // minutes
            'require_2fa' => false,
        ],

        'api_vendor' => [
            'token_lifetime' => 10080, // minutes (1 week)
            'refresh_token_lifetime' => 20160, // minutes (2 weeks)
            'max_tokens_per_user' => 20,
            'require_2fa' => false,
            'rate_limit' => [
                'max_attempts' => 500,
                'decay_minutes' => 1,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware configuration for different guards
    |
    */

    'middleware' => [
        'web' => [
            'throttle:web',
            'verified',
        ],

        'api' => [
            'throttle:api',
            'auth:sanctum',
        ],

        'admin' => [
            'throttle:admin',
            'verified',
            'role:admin,superadmin',
        ],

        'api_admin' => [
            'throttle:api_admin',
            'auth:sanctum',
            'role:admin,superadmin',
        ],

        'vendor' => [
            'throttle:vendor',
            'verified',
            'role:vendor',
        ],

        'api_vendor' => [
            'throttle:api_vendor',
            'auth:sanctum',
            'role:vendor',
        ],
    ],
];
