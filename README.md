# Muslim Finder Backend

A comprehensive Laravel-based backend API for the Muslim Finder application, featuring advanced user management, role-based access control, merchant management, and booking systems.

## 🚀 **Features**

### **🔐 Authentication & Security**
- **Laravel Sanctum** for API authentication
- **Spatie Laravel Permission** for role-based access control
- **Enhanced Guard System** with multiple authentication layers
- **Two-Factor Authentication (2FA)** with TOTP support
- **Email verification** and password reset functionality
- **Login history tracking** and security monitoring
- **Rate limiting** and IP whitelisting for admin access

### **👥 User Management System**
- **Multi-role hierarchy**: Superadmin, Admin, Vendor (Merchant), User
- **User profiles** with extended information
- **User preferences** and settings management
- **Activity logging** and audit trails
- **Notification system** with real-time updates
- **Session management** and security controls

### **🏪 Merchant/Vendor Management**
- **Comprehensive merchant profiles** with business information
- **Merchant settings** for API keys and configuration
- **Business verification** and status management
- **Location-based services** with geolocation support
- **Subscription management** and feature access control

### **📅 Booking System**
- **Complete booking lifecycle** management
- **Merchant-specific booking** views
- **Status tracking** and updates
- **Integration with external APIs** for enhanced functionality

### **🔌 API Integration**
- **External API integration** for products, packages, insurance
- **Webhook support** for real-time updates
- **Comprehensive API documentation** with Swagger/OpenAPI
- **Rate limiting** and API monitoring

## 🛠️ **Technology Stack**

### **Backend**
- **Laravel 12** - PHP framework
- **Laravel Sanctum** - API authentication
- **Spatie Laravel Permission** - Role & permission management
- **Spatie Laravel Activity Log** - Activity tracking
- **Spatie Laravel Media Library** - Media management
- **Google2FA Laravel** - Two-factor authentication
- **L5 Swagger** - API documentation

### **Frontend**
- **React 19** - Frontend framework
- **TailwindCSS 4** - Styling framework
- **Vite** - Build tool
- **Heroicons & Tabler Icons** - Icon libraries
- **SweetAlert2** - User notifications

### **Database**
- **SQLite** (development)
- **Eloquent ORM** for database operations
- **Comprehensive migrations** for all features

## 📋 **API Endpoints**

### **Authentication (`/api/v1/auth/`)**
- `POST /register` - User registration
- `POST /login` - User login
- `POST /login/{guard}` - Enhanced guard-based login
- `POST /logout` - User logout
- `GET /me` - Get current user
- `POST /refresh` - Refresh token
- `POST /setup-2fa` - Setup two-factor authentication
- `POST /verify-2fa` - Verify 2FA code
- `POST /forgot-password` - Password reset request
- `POST /reset-password` - Password reset

### **User Management (`/api/v1/users/`)**
- `GET /` - List users
- `GET /profile` - Get user profile
- `PUT /profile` - Update user profile
- `GET /statistics` - User statistics
- `POST /bulk-update` - Bulk user updates

### **Role & Permission Management (`/api/v1/roles/`, `/api/v1/permissions/`)**
- `GET /` - List roles/permissions
- `POST /` - Create role/permission
- `PUT /{id}` - Update role/permission
- `DELETE /{id}` - Delete role/permission
- `POST /{id}/assign-permissions` - Assign permissions to role

### **Merchant Management (`/api/v1/merchants/`)**
- `GET /` - List merchants
- `GET /profile` - Get merchant profile
- `PUT /profile` - Update merchant profile
- `GET /settings` - Get merchant settings
- `PUT /settings` - Update merchant settings

### **Booking System (`/api/v1/bookings/`)**
- `GET /` - List bookings
- `GET /my` - User's bookings
- `GET /merchant` - Merchant's bookings
- `POST /` - Create booking
- `PUT /{id}/status` - Update booking status

### **System & Health (`/api/v1/health/`, `/api/v1/system/`)**
- `GET /health/` - Basic health check
- `GET /health/detailed` - Detailed health status
- `GET /system/status` - System status
- `GET /system/metrics` - System metrics

## 🚀 **Installation & Setup**

### **Prerequisites**
- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL)

### **Installation Steps**

1. **Clone the repository**
   ```bash
   git clone https://github.com/nexworks-technology/muslim-finder-backend.git
   cd muslim-finder-backend
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

### **Development Setup**

For development with hot reloading:

```bash
# Start all development services
composer run dev

# Or start individually:
php artisan serve          # Laravel server
php artisan queue:listen   # Queue worker
php artisan pail          # Log viewer
npm run dev               # Vite dev server
```

## 🔧 **Configuration**

### **Environment Variables**
Key environment variables to configure:

```env
APP_NAME="Muslim Finder Backend"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DRIVER=database
SESSION_LIFETIME=120

# 2FA Configuration
GOOGLE2FA_APP_NAME="Muslim Finder"
```

### **Guards Configuration**
The application supports multiple authentication guards:

- **`web`** - Standard web authentication
- **`admin`** - Admin-specific authentication
- **`vendor`** - Vendor/merchant authentication
- **`api`** - Standard API authentication
- **`api_admin`** - Admin API authentication
- **`api_vendor`** - Vendor API authentication

## 📊 **User Roles & Permissions**

### **Role Hierarchy**

1. **Superadmin**
   - Ultimate system access
   - Can manage all users and roles
   - Full API access
   - Module access management

2. **Admin**
   - Administrative functions
   - User management (limited)
   - Merchant management
   - Booking oversight

3. **Vendor (Merchant)**
   - Manage own business profile
   - Handle own bookings
   - Access to merchant settings
   - Business analytics

4. **User**
   - Basic user functionality
   - Manage own profile
   - Create and manage bookings
   - View available merchants

## 🧪 **Testing**

```bash
# Run all tests
composer run test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit
```

## 📚 **API Documentation**

API documentation is available via Swagger/OpenAPI:

- **Development**: `http://localhost:8000/api/documentation`
- **Generated docs**: `storage/api-docs/api-docs.json`

## 🔍 **Monitoring & Logging**

The application includes comprehensive logging:

- **API request/response logging**
- **User activity tracking**
- **System event logging**
- **Error and exception logging**
- **Performance monitoring**

Logs are stored in `storage/logs/` with categorized files:
- `muslim-finder-{date}.log` - General logs
- `muslim-finder-success-{date}.log` - Success events
- `muslim-finder-error-{date}.log` - Error logs
- `muslim-finder-api-{date}.log` - API logs
- `muslim-finder-activity-{date}.log` - User activities

## 🚀 **Deployment**

### **Production Checklist**

1. Set `APP_ENV=production`
2. Set `APP_DEBUG=false`
3. Configure proper database
4. Set up SSL certificates
5. Configure web server (Nginx/Apache)
6. Set up queue workers
7. Configure log rotation
8. Set up monitoring

### **Docker Support**

The application includes Laravel Sail for Docker development:

```bash
./vendor/bin/sail up -d
```

## 🤝 **Contributing**

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## 📄 **License**

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 **Support**

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🔄 **Changelog**

### **v1.0.0** - Initial Release
- Complete user management system
- Role-based access control
- Merchant/vendor management
- Booking system
- API documentation
- Enhanced authentication system
- Comprehensive logging
- React frontend integration

---

**Built with ❤️ by the Nexworks Technology team**
