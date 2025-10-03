<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the superadmin role
        $superAdminRole = Role::where('name', 'superadmin')->first();
        
        if (!$superAdminRole) {
            $this->command->error('SuperAdmin role not found. Please run RolesTableSeeder first.');
            return;
        }

        // Check if superadmin user already exists
        $existingSuperAdmin = User::where('email', 'admin@shopexpressja.com')->first();
        
        if ($existingSuperAdmin) {
            $this->command->info('SuperAdmin user already exists.');
            return;
        }

        // Create the superadmin user
        $superAdmin = User::create([
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'email' => 'admin@shopexpressja.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'), // Change this in production!
            'role_id' => $superAdminRole->id,
        ]);

        // Generate unique account number
        $accountNumber = 'ADMIN' . str_pad($superAdmin->id, 3, '0', STR_PAD_LEFT);
        
        // Create a profile for the superadmin
        Profile::create([
            'user_id' => $superAdmin->id,
            'account_number' => $accountNumber,
            'tax_number' => '000-000-000',
            'telephone_number' => '+1-555-0000',
            'street_address' => '123 Admin Street',
            'city_town' => 'Admin City',
            'parish' => 'Admin Parish',
            'country' => 'Jamaica',
        ]);

        $this->command->info('SuperAdmin user created successfully!');
        $this->command->info('Email: admin@shopexpressja.com');
        $this->command->info('Password: password');
        $this->command->warn('Please change the default password in production!');
    }
}