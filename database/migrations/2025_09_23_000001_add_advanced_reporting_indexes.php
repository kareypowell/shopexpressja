<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdvancedReportingIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Advanced indexes for report query optimization based on actual query patterns
        
        // Packages table - additional composite indexes for complex report queries
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                // For revenue calculations by date and status
                if (!$this->indexExists('packages', 'packages_created_at_status_freight_price_index')) {
                    $table->index(['created_at', 'status', 'freight_price'], 'packages_created_at_status_freight_price_index');
                }
                
                // For user-specific financial reports
                if (!$this->indexExists('packages', 'packages_user_id_created_at_status_index')) {
                    $table->index(['user_id', 'created_at', 'status'], 'packages_user_id_created_at_status_index');
                }
                
                // For manifest performance analysis with weight/volume
                if (!$this->indexExists('packages', 'packages_manifest_id_weight_cubic_feet_index')) {
                    $table->index(['manifest_id', 'weight', 'cubic_feet'], 'packages_manifest_id_weight_cubic_feet_index');
                }
                
                // For office-based revenue reporting
                if (!$this->indexExists('packages', 'packages_office_id_freight_price_created_at_index')) {
                    $table->index(['office_id', 'freight_price', 'created_at'], 'packages_office_id_freight_price_created_at_index');
                }
                
                // For dimensional calculations (volume from dimensions)
                if (!$this->indexExists('packages', 'packages_length_width_height_index')) {
                    $table->index(['length_inches', 'width_inches', 'height_inches'], 'packages_length_width_height_index');
                }
            });
        }

        // Customer transactions table - enhanced indexes for financial reporting
        if (Schema::hasTable('customer_transactions')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                // For payment analysis with amounts
                if (!$this->indexExists('customer_transactions', 'customer_transactions_type_amount_created_at_index')) {
                    $table->index(['type', 'amount', 'created_at'], 'customer_transactions_type_amount_created_at_index');
                }
                
                // For user payment history analysis
                if (!$this->indexExists('customer_transactions', 'customer_transactions_user_id_amount_created_at_index')) {
                    $table->index(['user_id', 'amount', 'created_at'], 'customer_transactions_user_id_amount_created_at_index');
                }
            });
        }

        // Manifests table - enhanced indexes for performance reporting
        if (Schema::hasTable('manifests')) {
            Schema::table('manifests', function (Blueprint $table) {
                // For shipment date analysis with type
                if (!$this->indexExists('manifests', 'manifests_shipment_date_type_index')) {
                    $table->index(['shipment_date', 'type'], 'manifests_shipment_date_type_index');
                }
                
                // For manifest creation to shipment analysis
                if (!$this->indexExists('manifests', 'manifests_created_at_shipment_date_index')) {
                    $table->index(['created_at', 'shipment_date'], 'manifests_created_at_shipment_date_index');
                }
            });
        }

        // Package distributions table - for financial collection analysis
        if (Schema::hasTable('package_distributions')) {
            Schema::table('package_distributions', function (Blueprint $table) {
                // For revenue collection analysis
                if (!$this->indexExists('package_distributions', 'package_distributions_total_amount_created_at_index')) {
                    $table->index(['total_amount', 'created_at'], 'package_distributions_total_amount_created_at_index');
                }
            });
        }

        // Package distribution items table - for linking distributions to packages
        if (Schema::hasTable('package_distribution_items')) {
            Schema::table('package_distribution_items', function (Blueprint $table) {
                // For package-to-distribution linking in revenue calculations
                if (!$this->indexExists('package_distribution_items', 'package_distribution_items_package_id_distribution_id_index')) {
                    $table->index(['package_id', 'distribution_id'], 'package_distribution_items_package_id_distribution_id_index');
                }
            });
        }

        // Users table - for customer analytics
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // For account balance analysis
                if (!$this->indexExists('users', 'users_account_balance_created_at_index')) {
                    $table->index(['account_balance', 'created_at'], 'users_account_balance_created_at_index');
                }
                
                // For customer search optimization
                if (!$this->indexExists('users', 'users_first_name_last_name_index')) {
                    $table->index(['first_name', 'last_name'], 'users_first_name_last_name_index');
                }
            });
        }

        // Offices table - for office-based reporting
        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                // For office name searches in reports
                if (!$this->indexExists('offices', 'offices_name_index')) {
                    $table->index('name', 'offices_name_index');
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
        // Remove the advanced indexes
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'packages_created_at_status_freight_price_index');
                $this->dropIndexIfExists($table, 'packages_user_id_created_at_status_index');
                $this->dropIndexIfExists($table, 'packages_manifest_id_weight_cubic_feet_index');
                $this->dropIndexIfExists($table, 'packages_office_id_freight_price_created_at_index');
                $this->dropIndexIfExists($table, 'packages_length_width_height_index');
            });
        }

        if (Schema::hasTable('customer_transactions')) {
            Schema::table('customer_transactions', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'customer_transactions_type_amount_created_at_index');
                $this->dropIndexIfExists($table, 'customer_transactions_user_id_amount_created_at_index');
            });
        }

        if (Schema::hasTable('manifests')) {
            Schema::table('manifests', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'manifests_shipment_date_type_index');
                $this->dropIndexIfExists($table, 'manifests_created_at_shipment_date_index');
            });
        }

        if (Schema::hasTable('package_distributions')) {
            Schema::table('package_distributions', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'package_distributions_total_amount_created_at_index');
            });
        }

        if (Schema::hasTable('package_distribution_items')) {
            Schema::table('package_distribution_items', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'package_distribution_items_package_id_distribution_id_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'users_account_balance_created_at_index');
                $this->dropIndexIfExists($table, 'users_first_name_last_name_index');
            });
        }

        if (Schema::hasTable('offices')) {
            Schema::table('offices', function (Blueprint $table) {
                $this->dropIndexIfExists($table, 'offices_name_index');
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

    /**
     * Drop index if it exists
     *
     * @param Blueprint $table
     * @param string $indexName
     * @return void
     */
    private function dropIndexIfExists(Blueprint $table, string $indexName): void
    {
        $tableName = $table->getTable();
        if ($this->indexExists($tableName, $indexName)) {
            $table->dropIndex($indexName);
        }
    }
}