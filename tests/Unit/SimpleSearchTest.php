<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleSearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_search_users_by_first_name()
    {
        // Create a role
        $role = Role::create(['name' => 'customer', 'description' => 'Customer Role']);
        
        // Create a user
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $role->id,
        ]);

        // Create a profile
        Profile::factory()->create(['user_id' => $user->id]);

        // Test basic search
        $results = User::search('John')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }

    /** @test */
    public function it_can_search_users_with_profile_data()
    {
        // Create a role
        $role = Role::create(['name' => 'customer', 'description' => 'Customer Role']);
        
        // Create a user
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role_id' => $role->id,
        ]);

        // Create a profile with specific data
        Profile::factory()->create([
            'user_id' => $user->id,
            'account_number' => 'ACC123456',
        ]);

        // Test search by account number
        $results = User::search('ACC123456')->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->id);
    }
}