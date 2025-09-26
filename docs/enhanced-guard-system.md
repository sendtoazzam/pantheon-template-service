# Enhanced Guard System Documentation

## Overview

The Enhanced Guard System provides a comprehensive, multi-layered authentication and authorization system for the Pantheon Template Service. It supports multiple authentication guards, role-based access control, security features, and advanced user management.

## 🔐 **Guard Types**

### **Web Guards (Session-based)**
- **`web`** - Standard web authentication for regular users
- **`admin`** - Admin-specific web authentication with enhanced security
- **`vendor`** - Vendor-specific web authentication

### **API Guards (Token-based)**
- **`api`** - Standard API authentication using Laravel Sanctum
- **`api_admin`** - Admin API authentication with stricter security
- **`api_vendor`** - Vendor API authentication with business-specific settings

## 🛡️ **Security Features**

### **Rate Limiting**
- **Web Guards**: 5 failed attempts, 15-minute lockout
- **API Guards**: Configurable rate limits per guard type
- **Admin Guards**: 3 failed attempts, 30-minute lockout

### **Two-Factor Authentication (2FA)**
- **Required for**: Admin guards (`admin`, `api_admin`)
- **Optional for**: Other guards
- **Implementation**: TOTP-based 2FA with recovery codes

### **IP Whitelisting**
- **Admin Guards**: Support IP whitelisting for enhanced security
- **Configurable**: Per-guard IP restrictions

### **Token Management**
- **Secure Tokens**: 64-character cryptographically secure tokens
- **Token Limits**: Configurable max tokens per user per guard
- **Token Lifetime**: Different lifetimes for different guard types

## 📊 **User Types & Access**

### **Superadmin**
- **Access**: All guards (`web`, `api`, `admin`, `api_admin`, `vendor`, `api_vendor`)
- **Security**: Highest security requirements
- **Features**: Full system access, guard management

### **Admin**
- **Access**: Admin guards (`admin`, `api_admin`) + standard guards
- **Security**: Enhanced security with 2FA requirement
- **Features**: Administrative functions, user management

### **Vendor**
- **Access**: Vendor guards (`vendor`, `api_vendor`) + standard guards
- **Security**: Business-appropriate security settings
- **Features**: Merchant management, booking management

### **User**
- **Access**: Standard guards (`web`, `api`) only
- **Security**: Basic security settings
- **Features**: Profile management, basic functionality

## 🚀 **API Endpoints**

### **Authentication Endpoints**

#### **Login with Specific Guard**
```http
POST /api/v1/auth/login/{guard}
```

**Guards Available:**
- `web` - Web session authentication
- `api` - API token authentication
- `admin` - Admin web authentication
- `api_admin` - Admin API authentication
- `vendor` - Vendor web authentication
- `api_vendor` - Vendor API authentication

**Request Body:**
```json
{
  "login": "email@example.com",
  "password": "password",
  "remember_me": false,
  "two_factor_code": "123456"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { /* User object with roles and permissions */ },
    "token": "64-character-secure-token",
    "guard": "api_admin",
    "available_guards": ["web", "api", "admin", "api_admin"],
    "security_info": {
      "requires_2fa": true,
      "session_lifetime": 60,
      "rate_limit": { "max_attempts": 1000, "decay_minutes": 1 },
      "max_tokens": 5
    }
  }
}
```

#### **Get Available Guards**
```http
GET /api/v1/auth/guards
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "current_guard": "sanctum",
    "available_guards": ["web", "api", "admin", "api_admin"],
    "guard_info": {
      "web": { /* Security settings */ },
      "api": { /* Security settings */ },
      "admin": { /* Security settings */ },
      "api_admin": { /* Security settings */ }
    }
  }
}
```

#### **Switch Guard**
```http
POST /api/v1/auth/switch-guard
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "guard": "api_admin"
}
```

#### **Get Guard Statistics (Admin Only)**
```http
GET /api/v1/auth/guard-statistics
Authorization: Bearer {admin_token}
```

## ⚙️ **Configuration**

### **Guard Security Settings**

```php
// config/guards.php
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
    'admin' => [
        'session_lifetime' => 60, // minutes
        'remember_me_lifetime' => 10080, // minutes (1 week)
        'max_login_attempts' => 3,
        'lockout_duration' => 30, // minutes
        'require_2fa' => true,
        'ip_whitelist' => [], // Add IP addresses for admin access
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
```

## 🔧 **Middleware Usage**

### **Guard Middleware**
```php
Route::middleware('guard:api_admin')->group(function () {
    // Admin API routes
});
```

### **Role Middleware**
```php
Route::middleware('role:admin,superadmin')->group(function () {
    // Admin-only routes
});
```

### **Permission Middleware**
```php
Route::middleware('permission:manage users')->group(function () {
    // User management routes
});
```

## 📈 **User Model Enhancements**

### **New Fields**
- `is_admin` - Boolean flag for admin users
- `is_vendor` - Boolean flag for vendor users
- `is_active` - Boolean flag for active users
- `two_factor_secret` - 2FA secret key
- `two_factor_recovery_codes` - 2FA recovery codes
- `two_factor_confirmed_at` - 2FA confirmation timestamp
- `login_attempts` - Failed login attempt counter
- `locked_until` - Account lockout timestamp
- `last_login_ip` - Last login IP address
- `last_login_user_agent` - Last login user agent

### **Helper Methods**
```php
$user->isAdmin(); // Check if user is admin
$user->isVendor(); // Check if user is vendor
$user->isActive(); // Check if user is active
$user->isLocked(); // Check if user is locked
$user->hasTwoFactorEnabled(); // Check if 2FA is enabled
$user->getGuardForUser(); // Get appropriate web guard
$user->getApiGuardForUser(); // Get appropriate API guard
```

## 🔍 **Guard Service**

### **Available Methods**
```php
GuardService::getGuardForUser($user); // Get guard for user
GuardService::canAuthenticateWithGuard($user, $guard); // Check guard access
GuardService::getAvailableGuardsForUser($user); // Get available guards
GuardService::checkRateLimit($guard, $identifier); // Check rate limiting
GuardService::recordFailedLogin($user, $guard); // Record failed login
GuardService::resetLoginAttempts($user); // Reset login attempts
GuardService::requiresTwoFactor($guard); // Check 2FA requirement
GuardService::getGuardSecurity($guard); // Get security settings
GuardService::checkIpWhitelist($guard, $ip); // Check IP whitelist
GuardService::getGuardStatistics(); // Get guard statistics
```

## 🚨 **Error Handling**

### **Common Error Responses**

#### **Invalid Guard**
```json
{
  "success": false,
  "message": "Invalid guard specified",
  "error": {
    "guard": "invalid_guard",
    "available_guards": ["web", "api", "admin", "api_admin"]
  }
}
```

#### **Access Denied**
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to use this guard.",
  "error": {
    "code": "GUARD_ACCESS_DENIED",
    "guard": "api_admin",
    "available_guards": ["web", "api"]
  }
}
```

#### **Account Locked**
```json
{
  "success": false,
  "message": "Account is temporarily locked due to multiple failed login attempts.",
  "error": {
    "code": "ACCOUNT_LOCKED",
    "guard": "api_admin",
    "locked_until": "2025-09-26T08:30:00Z"
  }
}
```

#### **Rate Limit Exceeded**
```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "guard": "api_admin"
  }
}
```

#### **2FA Required**
```json
{
  "success": false,
  "message": "Two-factor authentication is required for this guard.",
  "error": {
    "code": "2FA_REQUIRED",
    "guard": "api_admin"
  }
}
```

## 🧪 **Testing Examples**

### **Test Admin Login**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/login/api_admin" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "superadmin@pantheon.com",
    "password": "password"
  }'
```

### **Test Vendor Login**
```bash
curl -X POST "http://127.0.0.1:8000/api/v1/auth/login/api_vendor" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "vendor@pantheon.com",
    "password": "password"
  }'
```

### **Test Guard Access**
```bash
curl -X GET "http://127.0.0.1:8000/api/v1/auth/guards" \
  -H "Authorization: Bearer {token}"
```

## 🔄 **Migration Guide**

### **From Basic Auth to Enhanced Guards**

1. **Run Migration**
   ```bash
   php artisan migrate
   ```

2. **Seed Database**
   ```bash
   php artisan db:seed --class=RolePermissionSeeder
   ```

3. **Update API Calls**
   - Change from `/api/v1/auth/login` to `/api/v1/auth/login/{guard}`
   - Handle new response format with guard information
   - Implement guard switching logic

4. **Update Frontend**
   - Handle guard-specific security requirements
   - Implement 2FA for admin guards
   - Show appropriate UI based on available guards

## 📚 **Best Practices**

1. **Use Appropriate Guards**: Choose the right guard for each user type
2. **Implement 2FA**: Enable 2FA for admin users
3. **Monitor Failed Logins**: Track and respond to suspicious activity
4. **Use Rate Limiting**: Implement appropriate rate limits for each guard
5. **Regular Security Audits**: Review guard access and permissions regularly
6. **Token Management**: Implement proper token cleanup and rotation
7. **IP Whitelisting**: Use IP whitelisting for sensitive admin access

## 🔮 **Future Enhancements**

- **JWT Integration**: Add JWT support for stateless authentication
- **OAuth2 Support**: Integrate with external OAuth2 providers
- **Biometric Authentication**: Add fingerprint/face recognition support
- **Advanced Analytics**: Detailed guard usage analytics
- **Custom Guard Types**: Support for custom guard implementations
- **Multi-tenant Guards**: Support for tenant-specific guards
