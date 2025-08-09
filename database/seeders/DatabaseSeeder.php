<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use App\Models\Employee;
use App\Models\Category;
use App\Models\Service;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if the settings table exists and is empty before seeding
        if (Schema::hasTable('settings') && Setting::count() === 0) {
            $setting = Setting::factory()->create();
            // Set the website logo
            $setting->update(['logo' => 'site-logo.png']);
        }

        // Check if the users table exists and is empty before creating user, permissions, and roles
        if (Schema::hasTable('users') && User::count() === 0) {
            $user = $this->createInitialUserWithPermissions();
            $this->createCategoriesAndServices($user);
        }
    }

    protected function createInitialUserWithPermissions()
    {
        // Define permissions list
        $permissions = [
            // Permission Management
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            // Appointment Management
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            // Category Management
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Service Management
            'services.view',
            'services.create',
            'services.edit',
            'services.delete',

            // Settings
            'settings.edit'
        ];

        // Create each permission if it doesn't exist
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Create roles if they do not exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $subscriberRole = Role::firstOrCreate(['name' => 'subscriber']);

        // Assign all permissions to the 'admin' role
        $adminRole->syncPermissions(Permission::all());

        // Create the initial admin user
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@darwinrg.me',
            'phone' => '1234567890',
            'status' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
        ]);

        // Assign specific permissions to the 'moderator' role
        $moderatorPermissions = [
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            'services.view',
            'services.create',
            'services.edit',
            'services.delete',
        ];

        $moderatorRole->syncPermissions(Permission::whereIn('name', $moderatorPermissions)->get());

        // Assign specific permissions to the 'employee' role
        $employeePermissions = [
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',
        ];

        $employeeRole->syncPermissions(Permission::whereIn('name', $employeePermissions)->get());

        // Assign the 'admin' role to the user
        $user->assignRole($adminRole);

        // Create DarwinRG as employee user
        $employeeUser = User::create([
            'name' => 'DarwinRG',
            'email' => 'darwinrguillermo11@gmail.com',
            'phone' => '09762640347',
            'status' => 1,
            'email_verified_at' => now(),
            'password' => Hash::make('pcst2526'),
        ]);

        // Assign employee role
        $employeeUser->assignRole($employeeRole);

        // Create employee profile for DarwinRG with schedule
        Employee::create([
            'user_id' => $employeeUser->id,
            'days' => [
                'monday' => ['09:00-16:00'],
                'tuesday' => ['09:00-16:00'],
                'wednesday' => ['09:00-16:00'],
                'thursday' => ['09:00-16:00'],
            ],
            'slot_duration' => 5,
        ]);

        return $user;
    }

    protected function createCategoriesAndServices(User $user)
    {
        // Create single category
        $category = Category::create([
            'title' => 'Student ID',
            'slug' => 'student-id',
            'body' => 'Student ID services and related assistance.',
            'image' => 'student-id-cat.png'
        ]);

        // Create single service for the category
        $idCreationService = Service::create([
            'title' => 'ID Creation',
            'slug' => 'id-creation',
            'excerpt' => 'Please fill out this form with your PanpacificU email before proceeding to get your student ID: 
https://bit.ly/StudentID-Urdaneta',
            'category_id' => $category->id,
            'image' => 'student-id-pickup.png'
        ]);

        // Attach only the ID Creation service to Darwin's employee profile
        $darwinEmployee = Employee::whereHas('user', function ($q) {
            $q->where('email', 'darwinrguillermo11@gmail.com');
        })->first();

        if ($darwinEmployee && $idCreationService) {
            $darwinEmployee->services()->sync([$idCreationService->id]);
        }
    }
}
