<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_transactions', function (Blueprint $table) {
            // Add manifest_id column for direct reference (optional optimization)
            $table->unsignedBigInteger('manifest_id')->nullable()->after('reference_id');
            
            // Add foreign key constraint
            $table->foreign('manifest_id')->references('id')->on('manifests')->onDelete('set null');
            
            // Add index for better query performance
            $table->index('manifest_id');
            
            // Add composite index for common queries
            $table->index(['user_id', 'manifest_id']);
            $table->index(['manifest_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_transactions', function (Blueprint $table) {
            // Drop foreign key and indexes first
            $table->dropForeign(['manifest_id']);
            $table->dropIndex(['user_id', 'manifest_id']);
            $table->dropIndex(['manifest_id', 'type']);
            $table->dropIndex(['manifest_id']);
            
            // Drop the column
            $table->dropColumn('manifest_id');
        });
    }
};