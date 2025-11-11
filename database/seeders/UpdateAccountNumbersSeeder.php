<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profile;
use Illuminate\Support\Facades\DB;

class UpdateAccountNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Updates all existing account numbers to the new ALQS8149-### format.
     * Assigns sequential numbers starting from 100.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting account number update...');

        // Get all profiles with old format account numbers (not already in new format)
        $profiles = Profile::where('account_number', 'NOT LIKE', 'ALQS8149-%')
            ->orderBy('id')
            ->get();

        if ($profiles->isEmpty()) {
            $this->command->info('No profiles found with old account number format.');
            return;
        }

        // Find the highest existing new-format account number
        $lastNewFormatAccount = Profile::where('account_number', 'LIKE', 'ALQS8149-%')
            ->orderByRaw('CAST(SUBSTRING(account_number, 10) AS UNSIGNED) DESC')
            ->first();

        $counter = $lastNewFormatAccount 
            ? ((int) substr($lastNewFormatAccount->account_number, 9)) + 1 
            : 100;

        $this->command->info("Found {$profiles->count()} profiles to update.");
        $this->command->info("Starting from account number: ALQS8149-{$counter}");

        DB::beginTransaction();

        try {
            foreach ($profiles as $profile) {
                if ($counter > 99999) {
                    throw new \RuntimeException('Account number limit reached. Maximum is ALQS8149-999');
                }

                $oldAccountNumber = $profile->account_number;
                $newAccountNumber = 'ALQS8149-' . $counter;

                $profile->account_number = $newAccountNumber;
                $profile->save();

                $this->command->info("Updated Profile ID {$profile->id}: {$oldAccountNumber} → {$newAccountNumber}");

                $counter++;
            }

            DB::commit();
            $this->command->info('✓ Successfully updated all account numbers!');
            $this->command->info("Total profiles updated: {$profiles->count()}");
            $this->command->info("Next available account number: ALQS8149-{$counter}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('✗ Error updating account numbers: ' . $e->getMessage());
            throw $e;
        }
    }
}
