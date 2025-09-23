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
        // Rename customs_duty to clearance_fee in packages table
        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('customs_duty', 'clearance_fee');
        });

        // Rename total_customs_duty to total_clearance_fee in consolidated_packages table
        Schema::table('consolidated_packages', function (Blueprint $table) {
            $table->renameColumn('total_customs_duty', 'total_clearance_fee');
        });

        // Rename customs_duty to clearance_fee in package_distribution_items table
        Schema::table('package_distribution_items', function (Blueprint $table) {
            $table->renameColumn('customs_duty', 'clearance_fee');
        });

        // Update indexes - drop old ones and create new ones
        Schema::table('packages', function (Blueprint $table) {
            // Drop the old index if it exists
            $indexName = 'packages_user_id_customs_duty_index';
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('packages');
            if (array_key_exists($indexName, $indexes)) {
                $table->dropIndex($indexName);
            }
            
            // Create new index
            $table->index(['user_id', 'clearance_fee'], 'packages_user_id_clearance_fee_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename clearance_fee back to customs_duty in packages table
        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('clearance_fee', 'customs_duty');
        });

        // Rename total_clearance_fee back to total_customs_duty in consolidated_packages table
        Schema::table('consolidated_packages', function (Blueprint $table) {
            $table->renameColumn('total_clearance_fee', 'total_customs_duty');
        });

        // Rename clearance_fee back to customs_duty in package_distribution_items table
        Schema::table('package_distribution_items', function (Blueprint $table) {
            $table->renameColumn('clearance_fee', 'customs_duty');
        });

        // Update indexes - drop new ones and create old ones
        Schema::table('packages', function (Blueprint $table) {
            // Drop the new index if it exists
            $indexName = 'packages_user_id_clearance_fee_index';
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('packages');
            if (array_key_exists($indexName, $indexes)) {
                $table->dropIndex($indexName);
            }
            
            // Create old index
            $table->index(['user_id', 'customs_duty'], 'packages_user_id_customs_duty_index');
        });
    }
};