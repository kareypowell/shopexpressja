<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewFieldsToCustomerTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_transactions', function (Blueprint $table) {
            $table->boolean('flagged_for_review')->default(false)->after('metadata');
            $table->text('review_reason')->nullable()->after('flagged_for_review');
            $table->timestamp('flagged_at')->nullable()->after('review_reason');
            $table->boolean('admin_notified')->default(false)->after('flagged_at');
            $table->timestamp('admin_notified_at')->nullable()->after('admin_notified');
            $table->boolean('review_resolved')->default(false)->after('admin_notified_at');
            $table->text('admin_response')->nullable()->after('review_resolved');
            $table->timestamp('resolved_at')->nullable()->after('admin_response');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['flagged_for_review', 'review_resolved']);
            $table->index('flagged_at');
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
            $table->dropForeign(['resolved_by']);
            $table->dropIndex(['flagged_for_review', 'review_resolved']);
            $table->dropIndex(['flagged_at']);
            
            $table->dropColumn([
                'flagged_for_review',
                'review_reason',
                'flagged_at',
                'admin_notified',
                'admin_notified_at',
                'review_resolved',
                'admin_response',
                'resolved_at',
                'resolved_by'
            ]);
        });
    }
}