<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManifestPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Check and add indexes only if they don't exist
        $this->addIndexIfNotExists('packages', ['manifest_id', 'weight'], 'packages_manifest_weight_perf_idx');
        $this->addIndexIfNotExists('packages', ['manifest_id', 'cubic_feet'], 'packages_manifest_volume_perf_idx');
        $this->addIndexIfNotExists('packages', ['manifest_id', 'length_inches', 'width_inches', 'height_inches'], 'packages_manifest_dimensions_perf_idx');
        $this->addIndexIfNotExists('packages', ['manifest_id', 'consolidated_package_id'], 'packages_manifest_consolidated_perf_idx');
        $this->addIndexIfNotExists('packages', ['manifest_id', 'freight_price'], 'packages_manifest_cost_perf_idx');
        
        $this->addIndexIfNotExists('manifests', ['type'], 'manifests_type_perf_idx');
        $this->addIndexIfNotExists('manifests', ['vessel_name', 'voyage_number'], 'manifests_vessel_perf_idx');
        $this->addIndexIfNotExists('manifests', ['flight_number', 'flight_destination'], 'manifests_flight_perf_idx');
        $this->addIndexIfNotExists('manifests', ['updated_at'], 'manifests_updated_at_perf_idx');
        
        $this->addIndexIfNotExists('consolidated_packages', ['customer_id'], 'consolidated_packages_customer_perf_idx');
        $this->addIndexIfNotExists('consolidated_packages', ['status'], 'consolidated_packages_status_perf_idx');
    }
    
    private function addIndexIfNotExists($table, array $columns, $indexName)
    {
        $indexExists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);
            
        if (!$indexExists) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $this->dropIndexIfExists('packages', 'packages_manifest_weight_perf_idx');
        $this->dropIndexIfExists('packages', 'packages_manifest_volume_perf_idx');
        $this->dropIndexIfExists('packages', 'packages_manifest_dimensions_perf_idx');
        $this->dropIndexIfExists('packages', 'packages_manifest_consolidated_perf_idx');
        $this->dropIndexIfExists('packages', 'packages_manifest_cost_perf_idx');
        
        $this->dropIndexIfExists('manifests', 'manifests_type_perf_idx');
        $this->dropIndexIfExists('manifests', 'manifests_vessel_perf_idx');
        $this->dropIndexIfExists('manifests', 'manifests_flight_perf_idx');
        $this->dropIndexIfExists('manifests', 'manifests_updated_at_perf_idx');
        
        $this->dropIndexIfExists('consolidated_packages', 'consolidated_packages_customer_perf_idx');
        $this->dropIndexIfExists('consolidated_packages', 'consolidated_packages_status_perf_idx');
    }
    
    private function dropIndexIfExists($table, $indexName)
    {
        $indexExists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);
            
        if ($indexExists) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
}

return AddManifestPerformanceIndexes::class;