<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Hash;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    function registration_page_contains_livewire_component()
    {
        $this->get(route('register'))
            ->assertSuccessful()
            ->assertSeeLivewire('auth.register');
    }

    /** @test */
    public function is_redirected_if_already_logged_in()
    {
        $user = User::factory()->create();

        $this->be($user);

        $this->get(route('register'))
            ->assertRedirect(route('home'));
    }

    /** @test */
    function a_user_can_register()
    {
        Event::fake();

        // Create the customer role
        \App\Models\Role::create([
            'name' => 'customer',
            'description' => 'Customer role for testing'
        ]);

        Livewire::test('auth.register')
            ->set('firstName', 'Tall')
            ->set('lastName', 'Stack')
            ->set('email', 'tallstack@example.com')
            ->set('password', 'password')
            ->set('passwordConfirmation', 'password')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->set('streetAddress', '123 Test St')
            ->set('townCity', 'Test City')
            ->set('parish', 'Test Parish')
            ->call('register')
            ->assertRedirect(route('home'));

        $this->assertTrue(User::whereEmail('tallstack@example.com')->exists());
        $this->assertEquals('tallstack@example.com', Auth::user()->email);

        Event::assertDispatched(Registered::class);
    }

    /** @test */
    function name_is_required()
    {
        Livewire::test('auth.register')
            ->set('firstName', '')
            ->set('lastName', '')
            ->call('register')
            ->assertHasErrors(['firstName' => 'required'])
            ->assertHasErrors(['lastName' => 'required']);
    }

    /** @test */
    function email_is_required()
    {
        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', '')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->call('register')
            ->assertHasErrors(['email' => 'required']);
    }

    /** @test */
    function email_is_valid_email()
    {
        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'tallstack')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->call('register')
            ->assertHasErrors(['email' => 'email']);
    }

    /** @test */
    function email_hasnt_been_taken_already()
    {
        User::factory()->create(['email' => 'tallstack@example.com']);

        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'tallstack@example.com')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    function see_email_hasnt_already_been_taken_validation_message_as_user_types()
    {
        User::factory()->create(['email' => 'tallstack@example.com']);

        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'smallstack@gmail.com')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->assertHasNoErrors()
            ->set('email', 'tallstack@example.com')
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    /** @test */
    function password_is_required()
    {
        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->set('password', '')
            ->set('passwordConfirmation', 'password')
            ->call('register')
            ->assertHasErrors(['password' => 'required']);
    }

    /** @test */
    function password_is_minimum_of_eight_characters()
    {
        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'test@example.com')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->set('password', 'secret')
            ->set('passwordConfirmation', 'secret')
            ->call('register')
            ->assertHasErrors(['password' => 'min']);
    }

    /** @test */
    function password_matches_password_confirmation()
    {
        Livewire::test('auth.register')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('email', 'tallstack@example.com')
            ->set('taxNumber', '1234567890')
            ->set('telephoneNumber', '1234567890')
            ->set('password', 'password')
            ->set('passwordConfirmation', 'not-password')
            ->call('register')
            ->assertHasErrors(['password' => 'same']);
    }
}
