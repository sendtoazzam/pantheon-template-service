<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage user roles',
            
            // Merchant/Vendor management
            'view merchants',
            'create merchants',
            'edit merchants',
            'delete merchants',
            'manage merchant settings',
            
            // Booking management
            'view bookings',
            'create bookings',
            'edit bookings',
            'delete bookings',
            'manage booking status',
            
            // Admin functions
            'view admin dashboard',
            'manage system settings',
            'view system logs',
            'manage roles',
            'manage permissions',
            'view analytics',
            
            // Profile management
            'edit own profile',
            'view own profile',
            
            // Merchant specific
            'manage own merchant profile',
            'view own bookings',
            'manage own bookings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create or get roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $vendorRole = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Assign permissions to roles
        
        // Superadmin gets all permissions
        $superadminRole->givePermissionTo(Permission::all());

        // Admin gets most permissions except superadmin-specific ones
        $adminRole->givePermissionTo([
            'view users', 'create users', 'edit users', 'delete users',
            'view merchants', 'create merchants', 'edit merchants', 'delete merchants', 'manage merchant settings',
            'view bookings', 'create bookings', 'edit bookings', 'delete bookings', 'manage booking status',
            'view admin dashboard', 'view system logs', 'view analytics',
            'edit own profile', 'view own profile',
        ]);

        // Vendor gets merchant and booking permissions
        $vendorRole->givePermissionTo([
            'manage own merchant profile', 'view own bookings', 'manage own bookings',
            'edit own profile', 'view own profile',
        ]);

        // User gets basic permissions
        $userRole->givePermissionTo([
            'view own profile', 'edit own profile',
            'view own bookings', 'create bookings', 'edit bookings',
        ]);

        // Create default users for each role
        $this->createDefaultUsers();
    }

    private function createDefaultUsers()
    {
        // Create or update superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@pantheon.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'phone' => '+1234567890',
                'status' => 'active',
                'is_admin' => true,
                'is_vendor' => false,
                'is_active' => true,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $superadmin->assignRole('superadmin');

        // Create or update admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@pantheon.com'],
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'phone' => '+1234567891',
                'status' => 'active',
                'is_admin' => true,
                'is_vendor' => false,
                'is_active' => true,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        // Create or update vendor user
        $vendor = User::firstOrCreate(
            ['email' => 'vendor@pantheon.com'],
            [
                'name' => 'Vendor User',
                'username' => 'vendor',
                'phone' => '+1234567892',
                'status' => 'active',
                'is_admin' => false,
                'is_vendor' => true,
                'is_active' => true,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $vendor->assignRole('vendor');

        // Create or update regular user
        $user = User::firstOrCreate(
            ['email' => 'user@pantheon.com'],
            [
                'name' => 'Regular User',
                'username' => 'user',
                'phone' => '+1234567893',
                'status' => 'active',
                'is_admin' => false,
                'is_vendor' => false,
                'is_active' => true,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole('user');
    }
}