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
            Setting::factory()->create();
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
            'email' => 'admin@example.com',
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

        // Assign the 'admin' role to the user
        $user->assignRole($adminRole);



         // Create admin as employee with additional details
        $employee = Employee::create([
            'user_id' => $user->id,
            'days' => [
                "monday" => ["06:00-22:00"],
                "tuesday" => ["06:00-15:00", "16:00-22:00"],
                "wednesday" => ["09:00-12:00", "14:00-23:00"],
                "thursday" => ["09:00-20:00"],
                "friday" => ["06:00-17:00"],
                "saturday" => ["05:00-18:00"]
            ],
            'slot_duration' => 30
        ]);

        return $user;
    }

    protected function createCategoriesAndServices(User $user)
    {
        // Create categories
        $categories = [
            [
                'title' => 'Test 1',
                'slug' => 'test1',
                'body' => 'This is a test category for demonstration purposes.'
            ],
            [
                'title' => 'Test 2',
                'slug' => 'test2',
                'body' => 'This is another test category for demonstration purposes.'
            ],
            [
                'title' => 'Test 3',
                'slug' => 'test3',
                'body' => 'This is yet another test category for demonstration purposes.'
            ]
        ];

        $services = [];

        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);

            // Create 2 services for each category
            switch ($category->title) {
                case 'Test 1':
                    $services = [
                        [
                            'title' => 'Math Tutoring',
                            'slug' => 'math-tutoring',
                            'price' => 500,
                            'excerpt' => 'Personalized math lessons for all grade levels.'
                        ],
                        [
                            'title' => 'Science Lab Workshop',
                            'slug' => 'science-lab-workshop',
                            'price' => 800,
                            'excerpt' => 'Hands-on experiments and science exploration.'
                        ]
                    ];
                    break;

                case 'Test 2':
                    $services = [
                        [
                            'title' => 'English Literature Class',
                            'slug' => 'english-literature-class',
                            'price' => 600,
                            'excerpt' => 'Explore classic and modern literature with expert guidance.'
                        ],
                        [
                            'title' => 'Creative Writing Workshop',
                            'slug' => 'creative-writing-workshop',
                            'price' => 700,
                            'excerpt' => 'Develop your writing skills in a supportive environment.'
                        ]
                    ];
                    break;

                case 'Test 3':
                    $services = [
                        [
                            'title' => 'Art & Craft Session',
                            'slug' => 'art-craft-session',
                            'price' => 400,
                            'excerpt' => 'Unleash creativity with fun art and craft activities.'
                        ],
                        [
                            'title' => 'Music Lessons',
                            'slug' => 'music-lessons',
                            'price' => 900,
                            'excerpt' => 'Learn instruments and music theory from professionals.'
                        ]
                    ];
                    break;
            
            }

            foreach ($services as $serviceData) {
                Service::create([
                    'title' => $serviceData['title'],
                    'slug' => $serviceData['slug'],
                    'price' => $serviceData['price'],
                    'excerpt' => $serviceData['excerpt'],
                    'category_id' => $category->id
                ]);
            }
        }

        // Attach all services to the employee (not directly to user)
        if ($user->employee) {
            $allServices = Service::all();
            $user->employee->services()->sync($allServices->pluck('id'));
        }
    }
}
