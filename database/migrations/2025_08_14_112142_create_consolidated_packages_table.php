<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConsolidatedPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consolidated_packages', function (Blueprint $table) {
            $table->id();
            $table->string('consolidated_tracking_number')->unique();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->decimal('total_weight', 15, 2)->default(0);
            $table->integer('total_quantity')->default(0);
            $table->decimal('total_freight_price', 15, 2)->default(0);
            $table->decimal('total_customs_duty', 15, 2)->default(0);
            $table->decimal('total_storage_fee', 15, 2)->default(0);
            $table->decimal('total_delivery_fee', 15, 2)->default(0);
            $table->string('status')->default('received');
            $table->timestamp('consolidated_at');
            $table->timestamp('unconsolidated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['customer_id', 'is_active']);
            $table->index('status');
            $table->index('consolidated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('consolidated_packages');
    }
}
