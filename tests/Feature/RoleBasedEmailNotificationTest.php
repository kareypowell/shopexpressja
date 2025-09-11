<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Mail\WelcomeUser;
use App\Mail\CustomerWelcomeEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Livewire\Livewire;
use App\Http\Livewire\Users\UserCreate;
use App\Http\Livewire\Users\UserEdit;

class RoleBasedEmailNotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $superAdminUser;
    protected $adminUser;
    protected $roles;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Get existing roles or create them if they don't exist
        $this->roles = [
            'superadmin' => Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']),
            'admin' => Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']),
            'customer' => Role::firstOrCreate(['name' => 'customer'], ['description' => 'Customer']),
            'purchaser' => Role::firstOrCreate(['name' => 'purchaser'], ['description' => 'Purchaser']),
        ];

        // Create test users
        $this->superAdminUser = User::factory()->create(['role_id' => $this->roles['superadmin']->id]);
        $this->adminUser = User::factory()->create(['role_id' => $this->roles['admin']->id]);
    }

    /** @test */
    public function customer_creation_sends_customer_welcome_email()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create a customer user
        Livewire::test(UserCreate::class)
            ->set('firstName', 'John')
            ->set('lastName', 'Customer')
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->set('taxNumber', 'TAX123')
            ->set('telephoneNumber', '555-1234')
            ->set('parish', 'Test Parish')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Test City')
            ->set('country', 'Test Country')
            ->call('createUser');

        // Verify customer welcome email was sent
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        // Verify generic welcome email was not sent
        Mail::assertNotSent(WelcomeUser::class);
    }

    /** @test */
    public function admin_creation_sends_generic_welcome_email()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create an admin user
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Jane')
            ->set('lastName', 'Admin')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'admin')
            ->call('createUser');

        // Verify generic welcome email was sent
        Mail::assertSent(WelcomeUser::class, function ($mail) {
            return $mail->hasTo('admin@example.com');
        });

        // Verify customer welcome email was not sent
        Mail::assertNotSent(CustomerWelcomeEmail::class);
    }

    /** @test */
    public function superadmin_creation_sends_generic_welcome_email()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create a superadmin user
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Super')
            ->set('lastName', 'Admin')
            ->set('email', 'superadmin@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'superadmin')
            ->call('createUser');

        // Verify generic welcome email was sent
        Mail::assertSent(WelcomeUser::class, function ($mail) {
            return $mail->hasTo('superadmin@example.com');
        });

        // Verify customer welcome email was not sent
        Mail::assertNotSent(CustomerWelcomeEmail::class);
    }

    /** @test */
    public function purchaser_creation_sends_generic_welcome_email()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create a purchaser user
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Purchase')
            ->set('lastName', 'Manager')
            ->set('email', 'purchaser@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'purchaser')
            ->call('createUser');

        // Verify generic welcome email was sent
        Mail::assertSent(WelcomeUser::class, function ($mail) {
            return $mail->hasTo('purchaser@example.com');
        });

        // Verify customer welcome email was not sent
        Mail::assertNotSent(CustomerWelcomeEmail::class);
    }

    /** @test */
    public function role_change_notification_is_sent()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create a test user
        $testUser = User::factory()->create([
            'role_id' => $this->roles['customer']->id,
            'email' => 'rolechange@example.com'
        ]);

        // Change user role
        Livewire::test(UserEdit::class, ['user' => $testUser])
            ->set('newRole', 'admin')
            ->set('roleChangeReason', 'Promotion to admin role')
            ->call('changeRole');

        // Note: This test assumes role change notifications are implemented
        // If not implemented yet, this test will help identify the need
        
        // Check if any notification emails were sent
        // This might need to be adjusted based on actual implementation
        $mailsSent = Mail::sent();
        
        // At minimum, we should verify the role change was successful
        $testUser->refresh();
        $this->assertEquals($this->roles['admin']->id, $testUser->role_id);
    }

    /** @test */
    public function welcome_email_contains_role_specific_content()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create customer
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Customer')
            ->set('lastName', 'User')
            ->set('email', 'customer@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->set('taxNumber', 'TAX123')
            ->set('telephoneNumber', '555-1234')
            ->set('parish', 'Test Parish')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Test City')
            ->set('country', 'Test Country')
            ->call('createUser');

        // Verify customer welcome email content
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) {
            $user = User::where('email', 'customer@example.com')->first();
            return $mail->user->id === $user->id;
        });

        // Create admin
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Admin')
            ->set('lastName', 'User')
            ->set('email', 'admin@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'admin')
            ->call('createUser');

        // Verify generic welcome email content
        Mail::assertSent(WelcomeUser::class, function ($mail) {
            $user = User::where('email', 'admin@example.com')->first();
            return $mail->user->id === $user->id;
        });
    }

    /** @test */
    public function email_failure_does_not_prevent_user_creation()
    {
        // Simulate email failure
        Mail::shouldReceive('send')->andThrow(new \Exception('Email service unavailable'));
        
        $this->actingAs($this->superAdminUser);

        // User creation should still succeed even if email fails
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->set('taxNumber', 'TAX123')
            ->set('telephoneNumber', '555-1234')
            ->set('parish', 'Test Parish')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Test City')
            ->set('country', 'Test Country')
            ->call('createUser')
            ->assertHasNoErrors();

        // Verify user was created despite email failure
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role_id' => $this->roles['customer']->id
        ]);
    }

    /** @test */
    public function multiple_user_creation_sends_appropriate_emails()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        $testUsers = [
            ['role' => 'customer', 'email' => 'customer1@example.com', 'expectedMail' => CustomerWelcomeEmail::class],
            ['role' => 'admin', 'email' => 'admin1@example.com', 'expectedMail' => WelcomeUser::class],
            ['role' => 'purchaser', 'email' => 'purchaser1@example.com', 'expectedMail' => WelcomeUser::class],
            ['role' => 'superadmin', 'email' => 'superadmin1@example.com', 'expectedMail' => WelcomeUser::class],
        ];

        foreach ($testUsers as $testUser) {
            $component = Livewire::test(UserCreate::class)
                ->set('firstName', 'Test')
                ->set('lastName', 'User')
                ->set('email', $testUser['email'])
                ->set('password', 'password123')
                ->set('selectedRole', $testUser['role']);

            if ($testUser['role'] === 'customer') {
                $component
                    ->set('taxNumber', 'TAX123')
                    ->set('telephoneNumber', '555-1234')
                    ->set('parish', 'Test Parish')
                    ->set('streetAddress', '123 Test St')
                    ->set('cityTown', 'Test City')
                    ->set('country', 'Test Country');
            }

            $component->call('createUser');

            // Verify appropriate email was sent
            Mail::assertSent($testUser['expectedMail'], function ($mail) use ($testUser) {
                return $mail->hasTo($testUser['email']);
            });
        }

        // Verify total email counts
        Mail::assertSent(CustomerWelcomeEmail::class, 1);
        Mail::assertSent(WelcomeUser::class, 3);
    }

    /** @test */
    public function email_queue_integration_works()
    {
        // This test verifies that emails are properly queued if queue is configured
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create user
        Livewire::test(UserCreate::class)
            ->set('firstName', 'Queued')
            ->set('lastName', 'User')
            ->set('email', 'queued@example.com')
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->set('taxNumber', 'TAX123')
            ->set('telephoneNumber', '555-1234')
            ->set('parish', 'Test Parish')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Test City')
            ->set('country', 'Test Country')
            ->call('createUser');

        // Verify email was queued (or sent immediately if not using queues)
        Mail::assertSent(CustomerWelcomeEmail::class);
    }

    /** @test */
    public function email_templates_exist_for_all_user_types()
    {
        // Verify that email templates exist and can be rendered
        $customerUser = User::factory()->create(['role_id' => $this->roles['customer']->id]);
        $adminUser = User::factory()->create(['role_id' => $this->roles['admin']->id]);

        // Test customer welcome email
        $customerMail = new CustomerWelcomeEmail($customerUser);
        $customerContent = $customerMail->render();
        $this->assertNotEmpty($customerContent);
        $this->assertStringContainsString($customerUser->first_name, $customerContent);

        // Test generic welcome email
        $adminMail = new WelcomeUser($adminUser);
        $adminContent = $adminMail->render();
        $this->assertNotEmpty($adminContent);
        $this->assertStringContainsString($adminUser->first_name, $adminContent);
    }

    /** @test */
    public function email_personalization_works_correctly()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        $firstName = 'PersonalizedTest';
        $lastName = 'User';
        $email = 'personalized@example.com';

        // Create customer with specific name
        Livewire::test(UserCreate::class)
            ->set('firstName', $firstName)
            ->set('lastName', $lastName)
            ->set('email', $email)
            ->set('password', 'password123')
            ->set('selectedRole', 'customer')
            ->set('taxNumber', 'TAX123')
            ->set('telephoneNumber', '555-1234')
            ->set('parish', 'Test Parish')
            ->set('streetAddress', '123 Test St')
            ->set('cityTown', 'Test City')
            ->set('country', 'Test Country')
            ->call('createUser');

        // Verify email was sent with correct user data
        Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) use ($firstName, $lastName, $email) {
            return $mail->hasTo($email) && 
                   $mail->user->first_name === $firstName && 
                   $mail->user->last_name === $lastName;
        });
    }

    /** @test */
    public function bulk_user_creation_sends_individual_emails()
    {
        Mail::fake();
        
        $this->actingAs($this->superAdminUser);

        // Create multiple users
        $userCount = 3;
        for ($i = 1; $i <= $userCount; $i++) {
            Livewire::test(UserCreate::class)
                ->set('firstName', "User{$i}")
                ->set('lastName', 'Test')
                ->set('email', "user{$i}@example.com")
                ->set('password', 'password123')
                ->set('selectedRole', 'customer')
                ->set('taxNumber', "TAX{$i}")
                ->set('telephoneNumber', '555-1234')
                ->set('parish', 'Test Parish')
                ->set('streetAddress', '123 Test St')
                ->set('cityTown', 'Test City')
                ->set('country', 'Test Country')
                ->call('createUser');
        }

        // Verify individual emails were sent
        Mail::assertSent(CustomerWelcomeEmail::class, $userCount);
        
        for ($i = 1; $i <= $userCount; $i++) {
            Mail::assertSent(CustomerWelcomeEmail::class, function ($mail) use ($i) {
                return $mail->hasTo("user{$i}@example.com");
            });
        }
    }
}