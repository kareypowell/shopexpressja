<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesAndIndexesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add soft delete functionality
            $table->softDeletes();
            
            // Add performance indexes
            $table->index('deleted_at');
            $table->index(['role_id', 'deleted_at']);
            $table->index(['email', 'deleted_at']);
            $table->index(['created_at', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['users_deleted_at_index']);
            $table->dropIndex(['users_role_id_deleted_at_index']);
            $table->dropIndex(['users_email_deleted_at_index']);
            $table->dropIndex(['users_created_at_deleted_at_index']);
            
            // Drop soft deletes column
            $table->dropSoftDeletes();
        });
    }
}