<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create-superadmin 
                            {--email= : Email address for the superadmin}
                            {--password= : Password for the superadmin}
                            {--name= : Full name for the superadmin}
                            {--force : Force creation even if superadmin exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superadmin user account';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating SuperAdmin Account...');

        // Get the superadmin role
        $superAdminRole = Role::where('name', 'superadmin')->first();
        
        if (!$superAdminRole) {
            $this->error('SuperAdmin role not found. Please run migrations and seeders first.');
            return 1;
        }

        // Get input values
        $email = $this->option('email') ?: $this->ask('Email address', 'admin@shipshark.com');
        $name = $this->option('name') ?: $this->ask('Full name', 'System Administrator');
        $password = $this->option('password') ?: $this->secret('Password (leave empty for "password")') ?: 'password';

        // Validate input
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser && !$this->option('force')) {
            $this->error("User with email {$email} already exists. Use --force to overwrite.");
            return 1;
        }

        if ($existingUser && $this->option('force')) {
            $this->warn("Deleting existing user with email {$email}...");
            $existingUser->profile()->delete();
            $existingUser->delete();
        }

        // Parse name for user creation
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';

        // Create the superadmin user
        $superAdmin = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
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

        $this->info('SuperAdmin user created successfully!');
        $this->info("Email: {$email}");
        $this->info("Name: {$name}");
        
        if ($password === 'password') {
            $this->warn('Using default password "password" - please change this in production!');
        }

        return 0;
    }
}
