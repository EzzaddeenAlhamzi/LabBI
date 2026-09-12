<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — single authoritative communications schema.
 * Avoids duplicate notification/PDF tables; extends critical_alerts for notify timestamps.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('test_order_id')->nullable()->constrained('test_orders')->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained('results')->nullOnDelete();
            $table->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('priority', 16)->default('normal'); // normal|critical
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('link_url')->nullable();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->string('channel', 32); // in_app|email|whatsapp
            $table->string('recipient')->nullable();
            $table->string('status', 32)->default('pending'); // pending|sent|failed
            $table->unsignedInteger('attempts')->default(0);
            $table->string('provider_ref')->nullable();
            $table->string('error_summary')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['channel', 'status']);
            $table->index(['notification_id', 'channel']);
        });

        Schema::create('generated_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained('results')->cascadeOnDelete();
            $table->foreignId('test_order_id')->constrained('test_orders')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('report_type', 32)->default('result_pdf');
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_key');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['patient_id', 'generated_at']);
            $table->index(['result_id', 'report_type']);
        });

        Schema::create('patient_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('result_id')->nullable()->constrained('results')->nullOnDelete();
            $table->foreignId('test_order_id')->nullable()->constrained('test_orders')->nullOnDelete();
            $table->string('type', 32); // interpretation|retest
            $table->text('message');
            $table->string('status', 32)->default('submitted'); // submitted|under_review|resolved|rejected
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'status']);
        });

        Schema::table('critical_alerts', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('discovered_at');
            $table->string('notification_status', 32)->nullable()->after('notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('critical_alerts', function (Blueprint $table) {
            $table->dropColumn(['notified_at', 'notification_status']);
        });
        Schema::dropIfExists('patient_requests');
        Schema::dropIfExists('generated_reports');
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
    }
};
