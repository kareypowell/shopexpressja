<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavedReportFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saved_report_filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('report_type'); // sales, manifest, customer, financial
            $table->json('filter_config'); // Date ranges, manifest types, offices, etc.
            $table->boolean('is_shared')->default(false);
            $table->json('shared_with_roles')->nullable(); // Array of role IDs that can access
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['user_id', 'report_type']);
            $table->index(['report_type', 'is_shared']);
            $table->index('user_id');
            
            // Unique constraint for user's filter names per report type
            $table->unique(['user_id', 'name', 'report_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saved_report_filters');
    }
}
