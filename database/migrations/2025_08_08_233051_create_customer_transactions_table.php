<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'payment', 'charge', 'credit', 'debit', 'distribution'
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('description');
            $table->string('reference_type')->nullable(); // 'package_distribution', 'manual_adjustment', etc.
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related record
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable(); // Additional data like package IDs, etc.
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};