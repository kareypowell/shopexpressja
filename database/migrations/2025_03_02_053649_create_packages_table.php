<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('manifest_id')->constrained()->onDelete('cascade');
            $table->foreignId('shipper_id')->constrained()->onDelete('cascade');
            $table->foreignId('office_id')->constrained()->onDelete('cascade');
            $table->string('warehouse_receipt_no')->index()->default('N/A');
            $table->string('tracking_number')->unique();
            $table->string('description');
            $table->decimal('weight', 15, 2);
            $table->decimal('value', 15, 2);
            $table->string('status')->default('Pending'); // Pending, Processing, Shipped, Delayed, Delivered
            $table->decimal('estimated_value', 15, 2)->default(0);
            $table->decimal('freight_price', 15, 2)->default(0);
            $table->decimal('processing_fee', 15, 2)->default(0);
            $table->decimal('customs_duty', 15, 2)->default(0);
            $table->decimal('storage_fee', 15, 2)->default(0);
            $table->decimal('pickup_fee', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packages');
    }
}
