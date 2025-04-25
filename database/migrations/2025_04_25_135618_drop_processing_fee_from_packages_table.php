<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropProcessingFeeFromPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Drop the processing_fee column from the packages table
            $table->dropColumn('processing_fee');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            // Recreate the processing_fee column in the packages table
            $table->decimal('processing_fee', 15, 2)->nullable()->after('freight_price');
        });
    }
}
