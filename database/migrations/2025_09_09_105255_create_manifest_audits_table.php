<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManifestAuditsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manifest_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manifest_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'closed', 'unlocked', 'auto_complete'
            $table->text('reason');
            $table->timestamp('performed_at');
            $table->timestamps();
            
            // Performance indexes for common queries
            $table->index(['manifest_id', 'performed_at']);
            $table->index(['user_id', 'performed_at']);
            $table->index(['action', 'performed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('manifest_audits');
    }
}
