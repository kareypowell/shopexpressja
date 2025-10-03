<?php

namespace App\Services;

use App\Models\Profile;

class AccountNumberService
{
    /**
     * Generate a unique account number with 'SHS' prefix and 7-digit number.
     * Implements collision detection to ensure uniqueness.
     *
     * @return string The generated unique account number
     */
    public function generate(): string
    {
        $maxAttempts = 100; // Prevent infinite loops
        $attempts = 0;

        do {
            $accountNumber = $this->generateAccountNumber();
            $attempts++;
            
            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException('Unable to generate unique account number after ' . $maxAttempts . ' attempts');
            }
        } while ($this->accountNumberExists($accountNumber));

        return $accountNumber;
    }

    /**
     * Generate a random account number with SHS prefix.
     *
     * @return string
     */
    private function generateAccountNumber(): string
    {
        return 'SEJA' . mt_rand(1000000, 9999999);
    }

    /**
     * Check if an account number already exists in the database.
     *
     * @param string $accountNumber
     * @return bool
     */
    protected function accountNumberExists(string $accountNumber): bool
    {
        return Profile::where('account_number', $accountNumber)->exists();
    }
}