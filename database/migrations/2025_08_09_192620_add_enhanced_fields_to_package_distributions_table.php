<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnhancedFieldsToPackageDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('package_distributions', function (Blueprint $table) {
            $table->decimal('write_off_amount', 10, 2)->default(0)->after('credit_applied');
            $table->text('notes')->nullable()->after('write_off_amount');
            $table->boolean('disputed')->default(false)->after('notes');
            $table->text('dispute_reason')->nullable()->after('disputed');
            $table->timestamp('disputed_at')->nullable()->after('dispute_reason');
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
            $table->dropColumn([
                'write_off_amount', 
                'notes',
                'disputed',
                'dispute_reason',
                'disputed_at'
            ]);
        });
    }
}