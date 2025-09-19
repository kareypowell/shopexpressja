<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWriteOffReasonToPackageDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->text('write_off_reason')->nullable()->after('write_off_amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->dropColumn('write_off_reason');
        });
    }
}
