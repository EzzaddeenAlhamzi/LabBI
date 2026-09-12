<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: results, reviews, critical alerts, attachments.
 * Demo/catalog ranges remain test-only until CAT-04 medical sign-off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->decimal('value_numeric', 14, 4)->nullable();
            $table->string('value_text')->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('classification', 32)->default('unknown');
            $table->string('status', 32)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reference_range_id')->nullable()->constrained('test_reference_ranges')->nullOnDelete();
            $table->json('range_snapshot')->nullable();
            $table->timestamps();
            $table->unique('test_order_item_id');
            $table->index(['status', 'classification']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('result_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 32);
            $table->text('comment')->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();
            $table->index(['result_id', 'action']);
        });

        Schema::create('critical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('detected_value', 14, 4);
            $table->string('unit', 50)->nullable();
            $table->json('threshold_snapshot')->nullable();
            $table->timestamp('discovered_at');
            $table->string('status', 32)->default('open');
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'discovered_at']);
            $table->index(['doctor_id', 'status']);
        });

        Schema::create('result_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('uploaded_at');
            $table->timestamps();
            $table->index(['patient_id', 'test_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_attachments');
        Schema::dropIfExists('critical_alerts');
        Schema::dropIfExists('result_reviews');
        Schema::dropIfExists('results');
    }
};
