<?php

namespace App\Services;

use App\Models\Profile;

class AccountNumberService
{
    /**
     * Generate a unique account number with 'ALQS8149-' prefix and sequential 3-digit suffix (100-999).
     * Uses sequential numbering starting from 100.
     *
     * @return string The generated unique account number
     */
    public function generate(): string
    {
        $nextNumber = $this->getNextSequentialNumber();
        
        if ($nextNumber > 999) {
            throw new \RuntimeException('Account number limit reached. Maximum is ALQS8149-999');
        }

        return 'ALQS8149-' . $nextNumber;
    }

    /**
     * Get the next sequential account number.
     * Finds the highest existing number and increments by 1.
     *
     * @return int
     */
    private function getNextSequentialNumber(): int
    {
        $lastAccount = Profile::where('account_number', 'LIKE', 'ALQS8149-%')
            ->orderByRaw('CAST(SUBSTRING(account_number, 10) AS UNSIGNED) DESC')
            ->first();

        if (!$lastAccount) {
            return 100; // Start from 100 if no accounts exist
        }

        $lastNumber = (int) substr($lastAccount->account_number, 9);
        return $lastNumber + 1;
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