<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->decimal('credit_applied', 10, 2)->default(0)->after('amount_collected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->dropColumn('credit_applied');
        });
    }
};