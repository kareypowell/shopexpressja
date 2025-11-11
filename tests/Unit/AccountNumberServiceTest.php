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
    private static int $userIdCounter = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountNumberService();
    }

    /** @test */
    public function it_generates_account_number_with_correct_prefix()
    {
        $accountNumber = $this->service->generate();

        $this->assertStringStartsWith('ALQS8149-', $accountNumber);
    }

    /** @test */
    public function it_generates_account_number_with_correct_format()
    {
        $accountNumber = $this->service->generate();

        // ALQS8149- (9 chars) + 3 digits = 12 total characters
        $this->assertEquals(12, strlen($accountNumber));
        $this->assertMatchesRegularExpression('/^ALQS8149-\d{3}$/', $accountNumber);
    }

    /** @test */
    public function it_generates_sequential_account_numbers()
    {
        $accountNumber1 = $this->service->generate();
        $this->createProfileWithAccountNumber($accountNumber1);
        
        $accountNumber2 = $this->service->generate();
        $this->createProfileWithAccountNumber($accountNumber2);

        // Extract numeric parts
        $num1 = (int) substr($accountNumber1, 9);
        $num2 = (int) substr($accountNumber2, 9);

        // Second number should be exactly 1 more than first
        $this->assertEquals($num1 + 1, $num2);
    }

    /** @test */
    public function it_starts_from_100_when_no_accounts_exist()
    {
        $accountNumber = $this->service->generate();

        $this->assertEquals('ALQS8149-100', $accountNumber);
    }

    /** @test */
    public function it_continues_from_existing_highest_number()
    {
        // Create profiles with specific account numbers
        $this->createProfileWithAccountNumber('ALQS8149-100');
        $this->createProfileWithAccountNumber('ALQS8149-101');
        $this->createProfileWithAccountNumber('ALQS8149-102');

        $accountNumber = $this->service->generate();

        $this->assertEquals('ALQS8149-103', $accountNumber);
    }

    /** @test */
    public function it_throws_exception_when_limit_reached()
    {
        // Create a profile with the maximum account number
        $this->createProfileWithAccountNumber('ALQS8149-999');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Account number limit reached');

        $this->service->generate();
    }

    /**
     * Helper method to create a profile with a specific account number
     * Creates a minimal user first to satisfy foreign key constraint.
     */
    private function createProfileWithAccountNumber(string $accountNumber): void
    {
        // Create a minimal role first if it doesn't exist
        $roleId = \DB::table('roles')->insertGetId([
            'name' => 'customer_' . self::$userIdCounter,
            'description' => 'Test Role',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create a minimal user
        $userId = \DB::table('users')->insertGetId([
            'first_name' => 'Test',
            'last_name' => 'User ' . self::$userIdCounter,
            'email' => 'test' . self::$userIdCounter . '@example.com',
            'password' => bcrypt('password'),
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        self::$userIdCounter++;

        // Create the profile
        \DB::table('profiles')->insert([
            'user_id' => $userId,
            'account_number' => $accountNumber,
            'tax_number' => 'TAX' . rand(100000, 999999),
            'telephone_number' => '555-0000',
            'street_address' => 'Test Address',
            'city_town' => 'Test City',
            'parish' => 'St. Andrew',
            'country' => 'Jamaica',
            'pickup_location' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_generates_numeric_suffix_within_valid_range()
    {
        $accountNumber = $this->service->generate();
        $numericPart = substr($accountNumber, 9); // Remove 'ALQS8149-' prefix

        $this->assertTrue(is_numeric($numericPart));
        $this->assertGreaterThanOrEqual(100, (int)$numericPart);
        $this->assertLessThanOrEqual(999, (int)$numericPart);
    }

    /** @test */
    public function it_generates_sequential_numbers_on_multiple_calls()
    {
        $numbers = [];
        
        // Generate 20 account numbers and persist them
        for ($i = 0; $i < 20; $i++) {
            $accountNumber = $this->service->generate();
            $this->createProfileWithAccountNumber($accountNumber);
            $numbers[] = $accountNumber;
        }

        // All numbers should be unique
        $uniqueNumbers = array_unique($numbers);
        $this->assertCount(20, $uniqueNumbers);

        // Verify they are sequential
        $this->assertEquals('ALQS8149-100', $numbers[0]);
        $this->assertEquals('ALQS8149-119', $numbers[19]);
    }
}