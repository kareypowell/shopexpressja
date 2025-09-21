<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditPerformanceIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Composite indexes for common query patterns
            $table->index(['event_type', 'created_at'], 'idx_audit_logs_event_type_date');
            $table->index(['user_id', 'created_at'], 'idx_audit_logs_user_date');
            $table->index(['action', 'created_at'], 'idx_audit_logs_action_date');
            $table->index(['auditable_type', 'created_at'], 'idx_audit_logs_auditable_type_date');
            
            // Composite index for security monitoring queries
            $table->index(['event_type', 'action', 'created_at'], 'idx_audit_logs_security_monitoring');
            
            // Index for IP-based queries (security analysis)
            $table->index(['ip_address', 'created_at'], 'idx_audit_logs_ip_date');
            
            // Index for user activity analysis
            $table->index(['user_id', 'event_type', 'created_at'], 'idx_audit_logs_user_activity');
            
            // Index for model-specific audit trails
            $table->index(['auditable_type', 'auditable_id', 'created_at'], 'idx_audit_logs_model_trail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_event_type_date');
            $table->dropIndex('idx_audit_logs_user_date');
            $table->dropIndex('idx_audit_logs_action_date');
            $table->dropIndex('idx_audit_logs_auditable_type_date');
            $table->dropIndex('idx_audit_logs_security_monitoring');
            $table->dropIndex('idx_audit_logs_ip_date');
            $table->dropIndex('idx_audit_logs_user_activity');
            $table->dropIndex('idx_audit_logs_model_trail');
        });
    }
}