<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBroadcastMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('broadcast_messages', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('content');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('recipient_type', ['all', 'selected'])->default('all');
            $table->integer('recipient_count')->default(0);
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'created_at']);
            $table->index(['sender_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('broadcast_messages');
    }
}
