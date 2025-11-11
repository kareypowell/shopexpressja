<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Profile;

class UpdateAccountNumbersToNewFormat extends Migration
{
    /**
     * Run the migrations.
     * Updates existing account numbers from ALQS#### format to ALQS8149-### format.
     *
     * @return void
     */
    public function up()
    {
        // Get all profiles with old format account numbers (ALQS followed by 4 digits)
        $profiles = Profile::where('account_number', 'LIKE', 'ALQS%')
            ->where('account_number', 'NOT LIKE', 'ALQS8149-%')
            ->get();

        $counter = 100; // Start from 100
        $usedNumbers = Profile::where('account_number', 'LIKE', 'ALQS8149-%')
            ->pluck('account_number')
            ->map(function ($accountNumber) {
                return (int) substr($accountNumber, -3);
            })
            ->toArray();

        foreach ($profiles as $profile) {
            // Find next available number
            while (in_array($counter, $usedNumbers) && $counter <= 99999) {
                $counter++;
            }

            if ($counter > 99999) {
                throw new \RuntimeException('Ran out of available account numbers (100-999)');
            }

            $newAccountNumber = 'ALQS8149-' . $counter;
            $profile->account_number = $newAccountNumber;
            $profile->save();

            $usedNumbers[] = $counter;
            $counter++;
        }
    }

    /**
     * Reverse the migrations.
     * Note: This cannot perfectly reverse the migration as the original random numbers are lost.
     *
     * @return void
     */
    public function down()
    {
        // Cannot reliably reverse this migration as we don't know the original account numbers
        // This is a one-way migration
    }
}
