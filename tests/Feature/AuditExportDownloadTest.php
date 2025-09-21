<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditExportDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create superadmin role and user
        $superAdminRole = Role::firstOrCreate(['name' => 'superadmin'], ['description' => 'Super Administrator']);
        $this->superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        Storage::fake('public');
    }

    /** @test */
    public function superadmin_can_download_csv_export()
    {
        // Create a test CSV file
        $filename = 'test_export.csv';
        $content = "ID,Date,Action\n1,2023-01-01,login\n2,2023-01-02,logout";
        Storage::disk('public')->put('exports/' . $filename, $content);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.audit-logs.download', ['filename' => $filename]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->assertEquals($content, $response->getContent());
    }

    /** @test */
    public function superadmin_can_download_pdf_export()
    {
        // Create a test PDF file (mock content)
        $filename = 'test_report.pdf';
        $content = '%PDF-1.4 mock pdf content';
        Storage::disk('public')->put('audit-reports/' . $filename, $content);

        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.audit-logs.download', ['filename' => $filename]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->assertEquals($content, $response->getContent());
    }

    /** @test */
    public function download_returns_404_for_nonexistent_file()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.audit-logs.download', ['filename' => 'nonexistent.csv']));

        $response->assertStatus(404);
    }

    /** @test */
    public function download_prevents_directory_traversal()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.audit-logs.download', ['filename' => '../../../etc/passwd']));

        $response->assertStatus(404);
    }

    /** @test */
    public function non_superadmin_cannot_download_exports()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create a test file
        $filename = 'test_export.csv';
        Storage::disk('public')->put('exports/' . $filename, 'test content');

        $this->actingAs($admin);

        $response = $this->get(route('admin.audit-logs.download', ['filename' => $filename]));

        $response->assertStatus(403);
    }
}