<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContainerAndDimensionalFieldsToPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->enum('container_type', ['box', 'barrel', 'pallet'])->nullable()->after('description');
            $table->decimal('length_inches', 8, 2)->nullable()->after('container_type');
            $table->decimal('width_inches', 8, 2)->nullable()->after('length_inches');
            $table->decimal('height_inches', 8, 2)->nullable()->after('width_inches');
            $table->decimal('cubic_feet', 10, 3)->nullable()->after('height_inches');
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
            $table->dropColumn([
                'container_type',
                'length_inches',
                'width_inches', 
                'height_inches',
                'cubic_feet'
            ]);
        });
    }
}
