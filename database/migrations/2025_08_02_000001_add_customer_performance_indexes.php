<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Only add indexes if tables exist
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                // Add indexes for customer statistics queries
                if (!$this->indexExists('packages', 'packages_user_id_status_index')) {
                    $table->index(['user_id', 'status']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_status_created_at_index')) {
                    $table->index(['user_id', 'status', 'created_at']);
                }
                if (!$this->indexExists('packages', 'packages_status_created_at_index')) {
                    $table->index(['status', 'created_at']);
                }
                
                // Add indexes for financial calculations
                if (!$this->indexExists('packages', 'packages_user_id_freight_price_index')) {
                    $table->index(['user_id', 'freight_price']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_customs_duty_index')) {
                    $table->index(['user_id', 'customs_duty']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_storage_fee_index')) {
                    $table->index(['user_id', 'storage_fee']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_delivery_fee_index')) {
                    $table->index(['user_id', 'delivery_fee']);
                }
                
                // Add composite index for weight and volume queries
                if (!$this->indexExists('packages', 'packages_user_id_weight_index')) {
                    $table->index(['user_id', 'weight']);
                }
                if (!$this->indexExists('packages', 'packages_user_id_cubic_feet_index')) {
                    $table->index(['user_id', 'cubic_feet']);
                }
            });
        }

        if (Schema::hasTable('profiles')) {
            Schema::table('profiles', function (Blueprint $table) {
                // Add indexes for customer search functionality
                if (!$this->indexExists('profiles', 'profiles_telephone_number_index')) {
                    $table->index('telephone_number');
                }
                if (!$this->indexExists('profiles', 'profiles_tax_number_index')) {
                    $table->index('tax_number');
                }
                if (!$this->indexExists('profiles', 'profiles_parish_index')) {
                    $table->index('parish');
                }
                if (!$this->indexExists('profiles', 'profiles_parish_city_town_index')) {
                    $table->index(['parish', 'city_town']);
                }
                if (!$this->indexExists('profiles', 'profiles_pickup_location_index')) {
                    $table->index('pickup_location');
                }
                
                // Add composite indexes for advanced search
                if (!$this->indexExists('profiles', 'profiles_user_id_parish_index')) {
                    $table->index(['user_id', 'parish']);
                }
                if (!$this->indexExists('profiles', 'profiles_user_id_pickup_location_index')) {
                    $table->index(['user_id', 'pickup_location']);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Add indexes for customer management queries (if not already present)
                if (!$this->indexExists('users', 'users_first_name_last_name_index')) {
                    $table->index(['first_name', 'last_name']);
                }
                
                if (!$this->indexExists('users', 'users_role_id_created_at_index')) {
                    $table->index(['role_id', 'created_at']);
                }
                
                if (!$this->indexExists('users', 'users_email_verified_at_index')) {
                    $table->index('email_verified_at');
                }
            });
        }

        // Add indexes for pre_alerts table if it affects customer statistics
        if (Schema::hasTable('pre_alerts')) {
            Schema::table('pre_alerts', function (Blueprint $table) {
                if (!$this->indexExists('pre_alerts', 'pre_alerts_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at']);
                }
                // Only add status index if status column exists
                if (Schema::hasColumn('pre_alerts', 'status') && !$this->indexExists('pre_alerts', 'pre_alerts_user_id_status_index')) {
                    $table->index(['user_id', 'status']);
                }
            });
        }

        // Add indexes for purchase_requests table if it affects customer statistics
        if (Schema::hasTable('purchase_requests')) {
            Schema::table('purchase_requests', function (Blueprint $table) {
                if (!$this->indexExists('purchase_requests', 'purchase_requests_user_id_created_at_index')) {
                    $table->index(['user_id', 'created_at']);
                }
                // Only add status index if status column exists
                if (Schema::hasColumn('purchase_requests', 'status') && !$this->indexExists('purchase_requests', 'purchase_requests_user_id_status_index')) {
                    $table->index(['user_id', 'status']);
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
        if (Schema::hasTable('packages')) {
            Schema::table('packages', function (Blueprint $table) {
                if ($this->indexExists('packages', 'packages_user_id_status_index')) {
                    $table->dropIndex(['user_id', 'status']);
                }
                if ($this->indexExists('packages', 'packages_user_id_created_at_index')) {
                    $table->dropIndex(['user_id', 'created_at']);
                }
                if ($this->indexExists('packages', 'packages_user_id_status_created_at_index')) {
                    $table->dropIndex(['user_id', 'status', 'created_at']);
                }
                if ($this->indexExists('packages', 'packages_status_created_at_index')) {
                    $table->dropIndex(['status', 'created_at']);
                }
                if ($this->indexExists('packages', 'packages_user_id_freight_price_index')) {
                    $table->dropIndex(['user_id', 'freight_price']);
                }
                if ($this->indexExists('packages', 'packages_user_id_customs_duty_index')) {
                    $table->dropIndex(['user_id', 'customs_duty']);
                }
                if ($this->indexExists('packages', 'packages_user_id_storage_fee_index')) {
                    $table->dropIndex(['user_id', 'storage_fee']);
                }
                if ($this->indexExists('packages', 'packages_user_id_delivery_fee_index')) {
                    $table->dropIndex(['user_id', 'delivery_fee']);
                }
                if ($this->indexExists('packages', 'packages_user_id_weight_index')) {
                    $table->dropIndex(['user_id', 'weight']);
                }
                if ($this->indexExists('packages', 'packages_user_id_cubic_feet_index')) {
                    $table->dropIndex(['user_id', 'cubic_feet']);
                }
            });
        }

        if (Schema::hasTable('profiles')) {
            Schema::table('profiles', function (Blueprint $table) {
                if ($this->indexExists('profiles', 'profiles_telephone_number_index')) {
                    $table->dropIndex(['telephone_number']);
                }
                if ($this->indexExists('profiles', 'profiles_tax_number_index')) {
                    $table->dropIndex(['tax_number']);
                }
                if ($this->indexExists('profiles', 'profiles_parish_index')) {
                    $table->dropIndex(['parish']);
                }
                if ($this->indexExists('profiles', 'profiles_parish_city_town_index')) {
                    $table->dropIndex(['parish', 'city_town']);
                }
                if ($this->indexExists('profiles', 'profiles_pickup_location_index')) {
                    $table->dropIndex(['pickup_location']);
                }
                if ($this->indexExists('profiles', 'profiles_user_id_parish_index')) {
                    $table->dropIndex(['user_id', 'parish']);
                }
                if ($this->indexExists('profiles', 'profiles_user_id_pickup_location_index')) {
                    $table->dropIndex(['user_id', 'pickup_location']);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_first_name_last_name_index')) {
                    $table->dropIndex(['first_name', 'last_name']);
                }
                if ($this->indexExists('users', 'users_role_id_created_at_index')) {
                    $table->dropIndex(['role_id', 'created_at']);
                }
                if ($this->indexExists('users', 'users_email_verified_at_index')) {
                    $table->dropIndex(['email_verified_at']);
                }
            });
        }

        if (Schema::hasTable('pre_alerts')) {
            Schema::table('pre_alerts', function (Blueprint $table) {
                if ($this->indexExists('pre_alerts', 'pre_alerts_user_id_created_at_index')) {
                    $table->dropIndex(['user_id', 'created_at']);
                }
                // Only drop status index if it exists
                if (Schema::hasColumn('pre_alerts', 'status') && $this->indexExists('pre_alerts', 'pre_alerts_user_id_status_index')) {
                    $table->dropIndex(['user_id', 'status']);
                }
            });
        }

        if (Schema::hasTable('purchase_requests')) {
            Schema::table('purchase_requests', function (Blueprint $table) {
                if ($this->indexExists('purchase_requests', 'purchase_requests_user_id_created_at_index')) {
                    $table->dropIndex(['user_id', 'created_at']);
                }
                // Only drop status index if it exists
                if (Schema::hasColumn('purchase_requests', 'status') && $this->indexExists('purchase_requests', 'purchase_requests_user_id_status_index')) {
                    $table->dropIndex(['user_id', 'status']);
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