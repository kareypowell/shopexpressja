<?php

namespace Tests\Unit;

use App\Models\Profile;
use App\Services\AccountNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountNumberService();
    }

    /** @test */
    public function it_generates_account_number_with_shs_prefix()
    {
        $accountNumber = $this->service->generate();

        $this->assertStringStartsWith('SHS', $accountNumber);
    }

    /** @test */
    public function it_generates_account_number_with_correct_length()
    {
        $accountNumber = $this->service->generate();

        // SHS (3 chars) + 7 digits = 10 total characters
        $this->assertEquals(10, strlen($accountNumber));
    }

    /** @test */
    public function it_generates_unique_account_numbers()
    {
        $accountNumber1 = $this->service->generate();
        $accountNumber2 = $this->service->generate();

        $this->assertNotEquals($accountNumber1, $accountNumber2);
    }

    /** @test */
    public function it_avoids_collision_with_existing_account_numbers()
    {
        // Create a profile with a specific account number
        $existingAccountNumber = 'SHS1234567';
        Profile::factory()->create(['account_number' => $existingAccountNumber]);

        // Generate multiple account numbers to ensure none collide
        $generatedNumbers = [];
        for ($i = 0; $i < 10; $i++) {
            $generatedNumbers[] = $this->service->generate();
        }

        // Ensure none of the generated numbers match the existing one
        $this->assertNotContains($existingAccountNumber, $generatedNumbers);
    }

    /** @test */
    public function it_throws_exception_when_unable_to_generate_unique_number()
    {
        // Create a custom service that always finds collisions
        $service = new class extends AccountNumberService {
            protected function accountNumberExists(string $accountNumber): bool
            {
                return true; // Always return true to simulate collision
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to generate unique account number after 100 attempts');

        $service->generate();
    }

    /** @test */
    public function it_generates_numeric_suffix_within_valid_range()
    {
        $accountNumber = $this->service->generate();
        $numericPart = substr($accountNumber, 3); // Remove 'SHS' prefix

        $this->assertTrue(is_numeric($numericPart));
        $this->assertGreaterThanOrEqual(1000000, (int)$numericPart);
        $this->assertLessThanOrEqual(9999999, (int)$numericPart);
    }

    /** @test */
    public function it_generates_different_numbers_on_multiple_calls()
    {
        $numbers = [];
        
        // Generate 20 account numbers
        for ($i = 0; $i < 20; $i++) {
            $numbers[] = $this->service->generate();
        }

        // All numbers should be unique
        $uniqueNumbers = array_unique($numbers);
        $this->assertCount(20, $uniqueNumbers);
    }
}