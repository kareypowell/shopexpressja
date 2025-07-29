<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVesselInformationToManifestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->string('vessel_name')->nullable()->after('flight_destination');
            $table->string('voyage_number')->nullable()->after('vessel_name');
            $table->string('departure_port')->nullable()->after('voyage_number');
            $table->string('arrival_port')->nullable()->after('departure_port');
            $table->date('estimated_arrival_date')->nullable()->after('arrival_port');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manifests', function (Blueprint $table) {
            $table->dropColumn([
                'vessel_name',
                'voyage_number', 
                'departure_port',
                'arrival_port',
                'estimated_arrival_date'
            ]);
        });
    }
}
