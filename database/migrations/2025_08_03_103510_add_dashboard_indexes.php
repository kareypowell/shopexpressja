<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDashboardIndexes extends Migration
{
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        return array_key_exists($indexName, $indexes);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add indexes for dashboard analytics queries
        
        // Users table indexes for customer analytics
        Schema::table('users', function (Blueprint $table) {
            // Check if indexes don't already exist before adding them
            if (!$this->indexExists('users', 'users_created_at_index')) {
                $table->index(['created_at'], 'users_created_at_index');
            }
            
            if (!$this->indexExists('users', 'users_active_status_index')) {
                $table->index(['email_verified_at', 'deleted_at'], 'users_active_status_index');
            }
            
            if (!$this->indexExists('users', 'users_created_verified_index')) {
                $table->index(['created_at', 'email_verified_at'], 'users_created_verified_index');
            }
        });

        // Packages table indexes for shipment and financial analytics
        Schema::table('packages', function (Blueprint $table) {
            // Check if indexes don't already exist before adding them
            if (!$this->indexExists('packages', 'packages_created_at_index')) {
                $table->index(['created_at'], 'packages_created_at_index');
            }
            
            if (!$this->indexExists('packages', 'packages_status_index')) {
                $table->index(['status'], 'packages_status_index');
            }
            
            if (!$this->indexExists('packages', 'packages_created_status_index')) {
                $table->index(['created_at', 'status'], 'packages_created_status_index');
            }
            
            if (!$this->indexExists('packages', 'packages_freight_price_index')) {
                $table->index(['freight_price'], 'packages_freight_price_index');
            }
            
            if (!$this->indexExists('packages', 'packages_created_freight_index')) {
                $table->index(['created_at', 'freight_price'], 'packages_created_freight_index');
            }
            
            if (!$this->indexExists('packages', 'packages_updated_at_index')) {
                $table->index(['updated_at'], 'packages_updated_at_index');
            }
            
            if (!$this->indexExists('packages', 'packages_processing_time_index')) {
                $table->index(['created_at', 'updated_at'], 'packages_processing_time_index');
            }
            
            if (!$this->indexExists('packages', 'packages_estimated_value_index')) {
                $table->index(['estimated_value'], 'packages_estimated_value_index');
            }
        });

        // Manifests table indexes for shipment analytics
        Schema::table('manifests', function (Blueprint $table) {
            if (!$this->indexExists('manifests', 'manifests_created_at_index')) {
                $table->index(['created_at'], 'manifests_created_at_index');
            }
            
            // Index for manifest status queries if status column exists
            if (Schema::hasColumn('manifests', 'status')) {
                if (!$this->indexExists('manifests', 'manifests_status_index')) {
                    $table->index(['status'], 'manifests_status_index');
                }
                if (!$this->indexExists('manifests', 'manifests_created_status_index')) {
                    $table->index(['created_at', 'status'], 'manifests_created_status_index');
                }
            }
        });

        // Pre-alerts table indexes if they exist
        if (Schema::hasTable('pre_alerts')) {
            Schema::table('pre_alerts', function (Blueprint $table) {
                $table->index(['created_at'], 'pre_alerts_created_at_index');
            });
        }

        // Rates table indexes for pricing analytics
        if (Schema::hasTable('rates')) {
            Schema::table('rates', function (Blueprint $table) {
                $table->index(['created_at'], 'rates_created_at_index');
                
                // Index for price-based queries
                if (Schema::hasColumn('rates', 'price')) {
                    $table->index(['price'], 'rates_price_index');
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
        // Remove dashboard analytics indexes
        
        Schema::table('users', function (Blueprint $table) {
            if ($this->indexExists('users', 'users_created_at_index')) {
                $table->dropIndex('users_created_at_index');
            }
            if ($this->indexExists('users', 'users_active_status_index')) {
                $table->dropIndex('users_active_status_index');
            }
            if ($this->indexExists('users', 'users_created_verified_index')) {
                $table->dropIndex('users_created_verified_index');
            }
        });

        Schema::table('packages', function (Blueprint $table) {
            if ($this->indexExists('packages', 'packages_created_at_index')) {
                $table->dropIndex('packages_created_at_index');
            }
            if ($this->indexExists('packages', 'packages_status_index')) {
                $table->dropIndex('packages_status_index');
            }
            if ($this->indexExists('packages', 'packages_created_status_index')) {
                $table->dropIndex('packages_created_status_index');
            }
            if ($this->indexExists('packages', 'packages_freight_price_index')) {
                $table->dropIndex('packages_freight_price_index');
            }
            if ($this->indexExists('packages', 'packages_created_freight_index')) {
                $table->dropIndex('packages_created_freight_index');
            }
            if ($this->indexExists('packages', 'packages_updated_at_index')) {
                $table->dropIndex('packages_updated_at_index');
            }
            if ($this->indexExists('packages', 'packages_processing_time_index')) {
                $table->dropIndex('packages_processing_time_index');
            }
            if ($this->indexExists('packages', 'packages_estimated_value_index')) {
                $table->dropIndex('packages_estimated_value_index');
            }
        });

        Schema::table('manifests', function (Blueprint $table) {
            if ($this->indexExists('manifests', 'manifests_created_at_index')) {
                $table->dropIndex('manifests_created_at_index');
            }
            
            if (Schema::hasColumn('manifests', 'status')) {
                if ($this->indexExists('manifests', 'manifests_status_index')) {
                    $table->dropIndex('manifests_status_index');
                }
                if ($this->indexExists('manifests', 'manifests_created_status_index')) {
                    $table->dropIndex('manifests_created_status_index');
                }
            }
        });

        if (Schema::hasTable('pre_alerts')) {
            Schema::table('pre_alerts', function (Blueprint $table) {
                $table->dropIndex('pre_alerts_created_at_index');
            });
        }

        if (Schema::hasTable('rates')) {
            Schema::table('rates', function (Blueprint $table) {
                $table->dropIndex('rates_created_at_index');
                
                if (Schema::hasColumn('rates', 'price')) {
                    $table->dropIndex('rates_price_index');
                }
            });
        }
    }
}
