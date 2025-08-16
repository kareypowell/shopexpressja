<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConsolidationPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consolidated_packages', function (Blueprint $table) {
            // Add composite index for customer queries with active status
            $table->index(['customer_id', 'is_active', 'status'], 'idx_consolidated_customer_active_status');
            
            // Add index for search queries on tracking number
            $table->index('consolidated_tracking_number', 'idx_consolidated_tracking_number');
            
            // Add index for date-based queries
            $table->index(['consolidated_at', 'is_active'], 'idx_consolidated_date_active');
            
            // Add index for status-based filtering
            $table->index(['status', 'is_active'], 'idx_consolidated_status_active');
        });

        Schema::table('packages', function (Blueprint $table) {
            // Add composite index for consolidated package queries
            $table->index(['consolidated_package_id', 'user_id'], 'idx_packages_consolidated_user');
            
            // Add index for consolidation eligibility queries
            $table->index(['user_id', 'is_consolidated', 'status'], 'idx_packages_user_consolidated_status');
            
            // Add index for search queries within consolidated packages
            $table->index(['is_consolidated', 'tracking_number'], 'idx_packages_consolidated_tracking');
            
            // Add index for consolidated package totals calculation
            $table->index(['consolidated_package_id', 'is_consolidated'], 'idx_packages_consolidated_totals');
        });

        Schema::table('consolidation_history', function (Blueprint $table) {
            // Add composite index for history queries by package and date
            $table->index(['consolidated_package_id', 'performed_at', 'action'], 'idx_history_package_date_action');
            
            // Add index for user-based history queries
            $table->index(['performed_by', 'performed_at'], 'idx_history_user_date');
            
            // Add index for action-based filtering
            $table->index(['action', 'performed_at'], 'idx_history_action_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consolidated_packages', function (Blueprint $table) {
            $table->dropIndex('idx_consolidated_customer_active_status');
            $table->dropIndex('idx_consolidated_tracking_number');
            $table->dropIndex('idx_consolidated_date_active');
            $table->dropIndex('idx_consolidated_status_active');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropIndex('idx_packages_consolidated_user');
            $table->dropIndex('idx_packages_user_consolidated_status');
            $table->dropIndex('idx_packages_consolidated_tracking');
            $table->dropIndex('idx_packages_consolidated_totals');
        });

        Schema::table('consolidation_history', function (Blueprint $table) {
            $table->dropIndex('idx_history_package_date_action');
            $table->dropIndex('idx_history_user_date');
            $table->dropIndex('idx_history_action_date');
        });
    }
}