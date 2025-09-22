<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReportingPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes to existing tables for optimal report query performance
        
        // Packages table - for sales and collections reporting
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                // Only add indexes that don't already exist
                if (!$this->indexExists('packages', 'packages_created_at_status_index')) {
                    $table->index(['created_at', 'status']); // For time-based status reports
                }
                if (!$this->indexExists('packages', 'packages_manifest_id_status_index')) {
                    $table->index(['manifest_id', 'status']); // For manifest performance reports
                }
                if (!$this->indexExists('packages', 'packages_office_id_created_at_index')) {
                    $table->index(['office_id', 'created_at']); // For office-based reports
                }
            });
        }

        // Customer transactions table - for financial reporting
        if (Schema::hasTable('customer_transactions')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                if (!$this->indexExists('customer_transactions', 'customer_transactions_type_created_at_index')) {
                    $table->index(['type', 'created_at']); // For payment/charge analysis
                }
                if (!$this->indexExists('customer_transactions', 'customer_transactions_user_id_type_created_at_index')) {
                    $table->index(['user_id', 'type', 'created_at']); // For customer financial reports
                }
                if (!$this->indexExists('customer_transactions', 'customer_transactions_reference_type_reference_id_index')) {
                    $table->index(['reference_type', 'reference_id']); // For package-transaction linking
                }
            });
        }

        // Manifests table - for manifest performance reporting
        if (Schema::hasTable('manifests')) {
            Schema::table('manifests', function (Blueprint $table) {
                if (!$this->indexExists('manifests', 'manifests_type_created_at_index')) {
                    $table->index(['type', 'created_at']); // For air/sea performance comparison
                }
                // Only add status index if status column exists
                if (Schema::hasColumn('manifests', 'status') && !$this->indexExists('manifests', 'manifests_status_created_at_index')) {
                    $table->index(['status', 'created_at']); // For status-based reporting
                }
            });
        }

        // Users table - for customer analytics (only add if not already present)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'users_created_at_index')) {
                    $table->index('created_at'); // For user registration trends
                }
            });
        }

        // Package distributions table - for financial reporting
        if (Schema::hasTable('package_distributions')) {
            Schema::table('package_distributions', function (Blueprint $table) {
                if (!$this->indexExists('package_distributions', 'package_distributions_created_at_total_amount_index')) {
                    $table->index(['created_at', 'total_amount']); // For revenue reporting
                }
                // Use customer_id instead of user_id for package distributions
                if (Schema::hasColumn('package_distributions', 'customer_id') && !$this->indexExists('package_distributions', 'package_distributions_customer_id_created_at_index')) {
                    $table->index(['customer_id', 'created_at']); // For customer distribution reports
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove the added indexes
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if ($this->indexExists('packages', 'packages_created_at_status_index')) {
                    $table->dropIndex(['created_at', 'status']);
                }
                if ($this->indexExists('packages', 'packages_manifest_id_status_index')) {
                    $table->dropIndex(['manifest_id', 'status']);
                }
                if ($this->indexExists('packages', 'packages_office_id_created_at_index')) {
                    $table->dropIndex(['office_id', 'created_at']);
                }
            });
        }

        if (Schema::hasTable('customer_transactions')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                if ($this->indexExists('customer_transactions', 'customer_transactions_type_created_at_index')) {
                    $table->dropIndex(['type', 'created_at']);
                }
                if ($this->indexExists('customer_transactions', 'customer_transactions_user_id_type_created_at_index')) {
                    $table->dropIndex(['user_id', 'type', 'created_at']);
                }
                if ($this->indexExists('customer_transactions', 'customer_transactions_reference_type_reference_id_index')) {
                    $table->dropIndex(['reference_type', 'reference_id']);
                }
            });
        }

        if (Schema::hasTable('manifests')) {
            Schema::table('manifests', function (Blueprint $table) {
                if ($this->indexExists('manifests', 'manifests_type_created_at_index')) {
                    $table->dropIndex(['type', 'created_at']);
                }
                // Only drop status index if it exists
                if (Schema::hasColumn('manifests', 'status') && $this->indexExists('manifests', 'manifests_status_created_at_index')) {
                    $table->dropIndex(['status', 'created_at']);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_created_at_index')) {
                    $table->dropIndex('created_at');
                }
            });
        }

        if (Schema::hasTable('package_distributions')) {
            Schema::table('package_distributions', function (Blueprint $table) {
                if ($this->indexExists('package_distributions', 'package_distributions_created_at_total_amount_index')) {
                    $table->dropIndex(['created_at', 'total_amount']);
                }
                // Use customer_id instead of user_id for package distributions
                if (Schema::hasColumn('package_distributions', 'customer_id') && $this->indexExists('package_distributions', 'package_distributions_customer_id_created_at_index')) {
                    $table->dropIndex(['customer_id', 'created_at']);
                }
            });
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        
        try {
            $indexes = $doctrineSchemaManager->listTableIndexes($table);
            return array_key_exists($index, $indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
}
