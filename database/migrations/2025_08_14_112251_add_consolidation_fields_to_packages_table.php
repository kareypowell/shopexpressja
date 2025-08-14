<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsolidationFieldsToPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('consolidated_package_id')->nullable()->constrained('consolidated_packages')->onDelete('set null');
            $table->boolean('is_consolidated')->default(false);
            $table->timestamp('consolidated_at')->nullable();
            
            // Index for performance
            $table->index(['consolidated_package_id', 'is_consolidated']);
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
            $table->dropForeign(['consolidated_package_id']);
            $table->dropIndex(['consolidated_package_id', 'is_consolidated']);
            $table->dropColumn(['consolidated_package_id', 'is_consolidated', 'consolidated_at']);
        });
    }
}
