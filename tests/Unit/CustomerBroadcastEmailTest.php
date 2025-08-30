<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Mail\CustomerBroadcastEmail;
use App\Models\BroadcastMessage;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class CustomerBroadcastEmailTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $broadcastMessage;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear existing data
        \DB::table('users')->delete();
        \DB::table('roles')->delete();

        // Create roles and users
        $customerRole = Role::factory()->create(['name' => 'customer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->customer = User::factory()->create([
            'role_id' => $customerRole->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ]);

        // Create broadcast message
        $this->broadcastMessage = BroadcastMessage::factory()->create([
            'sender_id' => $admin->id,
            'subject' => 'Important Update',
            'content' => '<p>This is a <strong>test message</strong> with HTML content.</p>'
        ]);
    }

    public function test_email_can_be_built()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        $built = $mailable->build();
        
        $this->assertEquals('Important Update', $built->subject);
        $this->assertEquals('emails.customer-broadcast', $built->view);
        $this->assertEquals('emails.customer-broadcast-text', $built->textView);
    }

    public function test_email_contains_customer_name()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $mailable->assertSeeInHtml('Dear John Doe');
        $mailable->assertSeeInText('Dear John Doe');
    }

    public function test_email_contains_broadcast_content()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $mailable->assertSeeInHtml('test message');
        $mailable->assertSeeInHtml('<strong>test message</strong>');
        $mailable->assertSeeInText('test message');
    }

    public function test_email_contains_company_information()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $companyName = config('app.name', 'ShipShark Ltd');
        $mailable->assertSeeInHtml($companyName);
        $mailable->assertSeeInText($companyName);
    }

    public function test_email_contains_customer_email_address()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $mailable->assertSeeInHtml('john@example.com');
        $mailable->assertSeeInText('john@example.com');
    }

    public function test_email_contains_unsubscribe_link()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $mailable->assertSeeInHtml('Unsubscribe');
        $mailable->assertSeeInText('unsubscribe');
    }

    public function test_email_contains_support_information()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $supportEmail = config('mail.support.address', config('mail.from.address'));
        $mailable->assertSeeInHtml($supportEmail);
        $mailable->assertSeeInText($supportEmail);
    }

    public function test_email_has_proper_queue_configuration()
    {
        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        $this->assertEquals(3, $mailable->tries);
        $this->assertEquals(60, $mailable->timeout);
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $mailable);
    }

    public function test_email_can_be_queued()
    {
        Mail::fake();

        $mailable = new CustomerBroadcastEmail($this->broadcastMessage, $this->customer);
        
        Mail::to($this->customer->email)->send($mailable);

        Mail::assertQueued(CustomerBroadcastEmail::class);
    }

    public function test_email_handles_html_content_properly()
    {
        $htmlContent = '<h1>Title</h1><p>Paragraph with <a href="http://example.com">link</a></p><ul><li>Item 1</li><li>Item 2</li></ul>';
        
        $broadcastMessage = BroadcastMessage::factory()->create([
            'subject' => 'HTML Test',
            'content' => $htmlContent
        ]);

        $mailable = new CustomerBroadcastEmail($broadcastMessage, $this->customer);
        
        $mailable->assertSeeInHtml('<h1>Title</h1>');
        $mailable->assertSeeInHtml('<a href="http://example.com">link</a>');
        $mailable->assertSeeInHtml('<ul><li>Item 1</li>');
        
        // Text version should strip HTML tags
        $mailable->assertSeeInText('Title');
        $mailable->assertSeeInText('Paragraph with link');
        $mailable->assertSeeInText('Item 1');
    }

    public function test_email_handles_empty_content()
    {
        $broadcastMessage = BroadcastMessage::factory()->create([
            'subject' => 'Empty Content Test',
            'content' => ''
        ]);

        $mailable = new CustomerBroadcastEmail($broadcastMessage, $this->customer);
        
        // Should still contain basic email structure
        $mailable->assertSeeInHtml('Dear John Doe');
        $mailable->assertSeeInText('Dear John Doe');
    }
}