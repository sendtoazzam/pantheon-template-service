# Generic Application Logging System

## Overview

This template includes a comprehensive, generic logging system with daily log rotation and separation by log levels. All logs are automatically prefixed with the application name (from `APP_NAME` in `.env`) and organized by type. This system is designed to be easily reusable across different projects.

## Log Configuration

### Daily Log Rotation
- **Retention**: 30 days (configurable via `LOG_DAILY_DAYS` env variable)
- **Naming**: `{app-name}-{type}-{date}.log` (app name from `APP_NAME` env variable)
- **Location**: `storage/logs/`

### Dynamic App Name
The logging system automatically uses the `APP_NAME` from your `.env` file and converts it to a URL-friendly format:
- `"My Awesome App"` → `my-awesome-app`
- `"Project Alpha"` → `project-alpha`
- `"API Service"` → `api-service`

### Log Channels

| Channel | File | Level | Purpose |
|---------|------|-------|---------|
| `{app-name}-daily` | `{app-name}-{date}.log` | debug | General application logs |
| `{app-name}-success` | `{app-name}-success-{date}.log` | info | Success messages |
| `{app-name}-error` | `{app-name}-error-{date}.log` | error | Error messages |
| `{app-name}-warning` | `{app-name}-warning-{date}.log` | warning | Warning messages |
| `{app-name}-api` | `{app-name}-api-{date}.log` | debug | API requests/responses |
| `{app-name}-activity` | `{app-name}-activity-{date}.log` | info | User activities |

## Usage

### Using PantheonLoggerService

```php
use App\Services\PantheonLoggerService;

// Success logs
PantheonLoggerService::success('Operation completed successfully');

// Error logs
PantheonLoggerService::error('Database connection failed', ['error' => $exception->getMessage()]);

// Warning logs
PantheonLoggerService::warning('High memory usage detected', ['usage' => '85%']);

// API logs
PantheonLoggerService::api('API endpoint called', ['endpoint' => '/api/users']);

// Activity logs
PantheonLoggerService::activity('User logged in', ['user_id' => 123]);

// User action logs
PantheonLoggerService::userAction(123, 'profile_updated', ['changes' => ['name', 'email']]);

// System event logs
PantheonLoggerService::systemEvent('maintenance_started', ['duration' => '2 hours']);
```

### Direct Laravel Log Usage

```php
use Illuminate\Support\Facades\Log;

// Using specific channels
Log::channel('pantheon-success')->info('Custom success message');
Log::channel('pantheon-error')->error('Custom error message');
Log::channel('pantheon-api')->info('Custom API message');
```

## Automatic API Logging

The `ApiLoggingMiddleware` automatically logs all API requests and responses:

- **Request logging**: Method, URL, data, timestamp
- **Response logging**: Status code, duration, memory usage
- **Performance metrics**: Response time in milliseconds
- **Memory tracking**: Current and peak memory usage

## Commands

### Test Logging
```bash
php artisan pantheon:test-logs
```

### View Logs
```bash
# View all logs
php artisan pantheon:logs

# View specific log type
php artisan pantheon:logs success
php artisan pantheon:logs error
php artisan pantheon:logs api
php artisan pantheon:logs activity

# View with custom line count
php artisan pantheon:logs api --lines=20
```

### Show App Configuration
```bash
# View current app configuration and log file names
php artisan app:config
```

## Using as a Template

### For New Projects
1. Copy this logging system to your new project
2. Update `APP_NAME` in your `.env` file
3. The logging system will automatically adapt to your app name

### Example
```bash
# In your .env file
APP_NAME="My New Project"

# Log files will be:
# my-new-project-2025-09-26.log
# my-new-project-success-2025-09-26.log
# my-new-project-error-2025-09-26.log
# etc.
```

### Configuration Files
- `config/app_logging.php` - Main logging configuration
- `config/logging.php` - Laravel logging channels (updated)
- `app/Services/PantheonLoggerService.php` - Logging service
- `app/Console/Commands/` - Log management commands

## API Endpoints

### Test Logging
```bash
GET /api/logs/test
```

This endpoint generates test logs for all log types and returns information about the generated log files.

## Log File Examples

### Success Log
```
[2025-09-26 01:46:08] local.INFO: User profile updated successfully {"user_id":123,"changes":["name","email"]}
```

### Error Log
```
[2025-09-26 01:46:08] local.ERROR: Database connection failed {"error":"Connection timeout","host":"localhost"}
```

### API Log
```
[2025-09-26 01:46:19] local.INFO: API Request: GET /api/health {"method":"GET","url":"/api/health","data":[],"response_code":200,"timestamp":"2025-09-26T01:46:19.594514Z"}
```

### Activity Log
```
[2025-09-26 01:46:08] local.INFO: User Action: profile_updated {"user_id":123,"action":"profile_updated","timestamp":"2025-09-26T01:46:08.000000Z"}
```

## Configuration

### Environment Variables

```env
# Default log channel
LOG_CHANNEL=pantheon-daily

# Log level
LOG_LEVEL=debug

# Daily log retention (days)
LOG_DAILY_DAYS=30
```

### Log Rotation

Logs are automatically rotated daily and old logs are cleaned up based on the `LOG_DAILY_DAYS` setting. The system uses Laravel's built-in daily log driver.

## Best Practices

1. **Use appropriate log levels**: success, error, warning, info, debug
2. **Include context**: Always provide relevant context data
3. **Avoid sensitive data**: Don't log passwords, tokens, or personal information
4. **Use structured logging**: Include arrays/objects for better parsing
5. **Monitor log sizes**: Large log files can impact performance
6. **Regular cleanup**: Ensure old logs are properly rotated

## Integration with Spatie Activity Log

The logging system integrates with Spatie Activity Log for comprehensive activity tracking:

```php
// This will log to both Spatie Activity Log and Pantheon Activity Log
activity()
    ->causedBy($user)
    ->performedOn($model)
    ->log('User updated profile');
```

## Troubleshooting

### Log Files Not Created
- Check `storage/logs/` directory permissions
- Ensure Laravel has write access to the storage directory

### Missing Log Entries
- Verify the log channel is correctly configured
- Check the log level settings
- Ensure the PantheonLoggerService is properly imported

### Performance Issues
- Monitor log file sizes
- Consider reducing log retention period
- Use appropriate log levels to avoid excessive logging
