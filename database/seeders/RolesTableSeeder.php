<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('roles')->insert([
            [
                'name' => 'superadmin',
                'description' => 'Super Administrator is the owner of the system',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'admin', 
                'description' => 'Administrator is allowed to manage the essentials',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'customer', 
                'description' => 'Customer is allowed to manage and edit only his/her own profile',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'purchaser', 
                'description' => 'Purchaser is allowed to manage and edit only his/her own profile',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
