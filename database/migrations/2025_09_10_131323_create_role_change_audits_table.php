<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoleChangeAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('role_change_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('changed_by_user_id');
            $table->unsignedBigInteger('old_role_id')->nullable();
            $table->unsignedBigInteger('new_role_id');
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('changed_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('old_role_id')->references('id')->on('roles')->onDelete('set null');
            $table->foreign('new_role_id')->references('id')->on('roles')->onDelete('cascade');

            // Indexes for performance
            $table->index('user_id');
            $table->index('changed_by_user_id');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_change_audits');
    }
}
