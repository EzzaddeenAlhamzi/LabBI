<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — single authoritative BI migration.
 * Adds workflow timestamps needed for real TAT + read-model indexes.
 * Does not invent clinical data; stamps only when transitions already occur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_orders', function (Blueprint $table) {
            $table->timestamp('analysis_started_at')->nullable()->after('ordered_at');
            $table->timestamp('completed_at')->nullable()->after('archived_at');
            $table->index(['department_id', 'status', 'ordered_at'], 'test_orders_dept_status_ordered_idx');
            $table->index(['completed_at'], 'test_orders_completed_at_idx');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->index(['status', 'received_at'], 'samples_status_received_idx');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('entered_at');
            $table->index(['test_id', 'status', 'approved_at'], 'results_test_status_approved_idx');
            $table->index(['patient_id', 'test_id', 'status', 'approved_at'], 'results_patient_test_approved_idx');
        });

        Schema::table('critical_alerts', function (Blueprint $table) {
            $table->index(['test_id', 'status', 'discovered_at'], 'critical_alerts_test_status_discovered_idx');
            $table->index(['doctor_id', 'discovered_at'], 'critical_alerts_doctor_discovered_idx');
        });
    }

    public function down(): void
    {
        Schema::table('critical_alerts', function (Blueprint $table) {
            $table->dropIndex('critical_alerts_test_status_discovered_idx');
            $table->dropIndex('critical_alerts_doctor_discovered_idx');
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex('results_test_status_approved_idx');
            $table->dropIndex('results_patient_test_approved_idx');
            $table->dropColumn('submitted_at');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropIndex('samples_status_received_idx');
        });

        Schema::table('test_orders', function (Blueprint $table) {
            $table->dropIndex('test_orders_dept_status_ordered_idx');
            $table->dropIndex('test_orders_completed_at_idx');
            $table->dropColumn(['analysis_started_at', 'completed_at']);
        });
    }
};
