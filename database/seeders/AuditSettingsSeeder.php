<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditSetting;

class AuditSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Initialize default audit settings
        AuditSetting::initializeDefaults();
    }
}
