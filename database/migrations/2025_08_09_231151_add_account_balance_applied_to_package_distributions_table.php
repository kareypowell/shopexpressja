<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountBalanceAppliedToPackageDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->decimal('account_balance_applied', 10, 2)->default(0)->after('credit_applied');
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
            $table->dropColumn('account_balance_applied');
        });
    }
}