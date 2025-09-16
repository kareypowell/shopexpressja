<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestoreLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restore_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('backup_id');
            $table->unsignedBigInteger('restored_by');
            $table->enum('restore_type', ['database', 'files', 'full']);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('pre_restore_backup_path')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('backup_id')->references('id')->on('backups')->onDelete('cascade');
            $table->foreign('restored_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['backup_id', 'status']);
            $table->index(['restored_by', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restore_logs');
    }
}
