<?php

namespace Tests\Feature;

use App\Models\Manifest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ManifestUnlockedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManifestUnlockEmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $recipientUser;
    protected Manifest $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        
        // Create users
        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->recipientUser = User::factory()->create(['role_id' => $adminRole->id]);
        
        // Create manifest with some test data
        $this->manifest = Manifest::factory()->create([
            'name' => 'Test Manifest for Email',
            'type' => 'sea',
            'is_open' => false,
        ]);
    }

    public function test_email_template_renders_without_errors()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Testing email template rendering with a detailed reason for unlocking this manifest.',
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);

        // Test that the mail message is created successfully
        $this->assertNotNull($mailMessage);
        $this->assertEquals('emails.admin.manifest-unlocked', $mailMessage->view);
        
        // Test view data is properly set
        $viewData = $mailMessage->viewData;
        $this->assertArrayHasKey('manifest', $viewData);
        $this->assertArrayHasKey('unlockedBy', $viewData);
        $this->assertArrayHasKey('reason', $viewData);
        $this->assertArrayHasKey('unlockedAt', $viewData);
        $this->assertArrayHasKey('recipient', $viewData);
    }

    public function test_email_template_renders_with_view()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Testing email template view rendering.',
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        
        // Render the view to ensure it doesn't throw errors
        $renderedView = view($mailMessage->view, $mailMessage->viewData)->render();
        
        // Check that the rendered view contains expected content
        $this->assertStringContainsString('Manifest Unlocked', $renderedView);
        $this->assertStringContainsString($this->manifest->name, $renderedView);
        $this->assertStringContainsString($this->adminUser->full_name, $renderedView);
        $this->assertStringContainsString('Testing email template view rendering.', $renderedView);
    }

    public function test_email_template_handles_special_characters()
    {
        // Create manifest and user with special characters
        $specialManifest = Manifest::factory()->create([
            'name' => 'Manifest with Special Chars: áéíóú & "quotes" & \'apostrophes\'',
            'type' => 'air',
        ]);

        $specialUser = User::factory()->create([
            'first_name' => 'José',
            'last_name' => 'García-López',
            'role_id' => Role::where('name', 'admin')->first()->id,
        ]);

        $specialReason = 'Reason with special chars: 中文, emoji 🚀, quotes "test" and \'test\'';

        $notification = new ManifestUnlockedNotification(
            $specialManifest,
            $specialUser,
            $specialReason,
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        $renderedView = view($mailMessage->view, $mailMessage->viewData)->render();
        
        // Check that special characters are properly handled
        $this->assertStringContainsString('José García-López', $renderedView);
        $this->assertStringContainsString('áéíóú', $renderedView);
        $this->assertStringContainsString('中文', $renderedView);
        $this->assertStringContainsString('🚀', $renderedView);
    }

    public function test_email_template_handles_long_content()
    {
        $longReason = str_repeat('This is a very long reason for unlocking the manifest that should test how the email template handles extensive text content. ', 10);
        
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            $longReason,
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        $renderedView = view($mailMessage->view, $mailMessage->viewData)->render();
        
        // Check that long content is properly rendered
        $this->assertStringContainsString($longReason, $renderedView);
        $this->assertGreaterThan(1000, strlen($renderedView)); // Should be a substantial email
    }

    public function test_email_template_includes_audit_trail_section()
    {
        // Create some audit records for the manifest
        $this->manifest->audits()->create([
            'user_id' => $this->adminUser->id,
            'action' => 'closed',
            'reason' => 'All packages delivered',
            'performed_at' => Carbon::now()->subHours(2),
        ]);

        $this->manifest->audits()->create([
            'user_id' => $this->adminUser->id,
            'action' => 'unlocked',
            'reason' => 'Need to update package information',
            'performed_at' => Carbon::now()->subHour(),
        ]);

        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Testing audit trail display',
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        $renderedView = view($mailMessage->view, $mailMessage->viewData)->render();
        
        // Check that audit trail section is included
        $this->assertStringContainsString('Recent Audit Activity', $renderedView);
        $this->assertStringContainsString('All packages delivered', $renderedView);
        $this->assertStringContainsString('Need to update package information', $renderedView);
    }

    public function test_email_template_includes_action_buttons()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Testing action buttons',
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        $renderedView = view($mailMessage->view, $mailMessage->viewData)->render();
        
        // Check that action buttons are included
        $this->assertStringContainsString('View Manifest', $renderedView);
        $this->assertStringContainsString('All Manifests', $renderedView);
        $this->assertStringContainsString('/admin/manifests/', $renderedView);
    }

    public function test_email_subject_format()
    {
        $notification = new ManifestUnlockedNotification(
            $this->manifest,
            $this->adminUser,
            'Testing subject format',
            Carbon::now()
        );

        $mailMessage = $notification->toMail($this->recipientUser);
        
        $expectedSubject = 'Manifest Unlocked - ' . $this->manifest->name;
        $this->assertEquals($expectedSubject, $mailMessage->subject);
    }
}