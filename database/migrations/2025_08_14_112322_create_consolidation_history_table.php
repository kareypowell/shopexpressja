<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsolidationHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consolidation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consolidated_package_id')->constrained('consolidated_packages')->onDelete('cascade');
            $table->string('action'); // 'consolidated', 'unconsolidated', 'status_changed'
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->json('details')->nullable(); // Additional context data
            $table->timestamp('performed_at');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['consolidated_package_id', 'performed_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consolidation_history');
    }
}
