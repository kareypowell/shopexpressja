<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCubicFeetSupportToRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rates', function (Blueprint $table) {
            // Make weight nullable for sea rates
            $table->decimal('weight', 10, 2)->nullable()->change();
            
            // Add cubic feet range fields for sea rates
            $table->decimal('min_cubic_feet', 10, 3)->nullable()->after('weight');
            $table->decimal('max_cubic_feet', 10, 3)->nullable()->after('min_cubic_feet');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rates', function (Blueprint $table) {
            // Remove cubic feet fields
            $table->dropColumn(['min_cubic_feet', 'max_cubic_feet']);
            
            // Make weight required again
            $table->decimal('weight', 10, 2)->nullable(false)->change();
        });
    }
}
