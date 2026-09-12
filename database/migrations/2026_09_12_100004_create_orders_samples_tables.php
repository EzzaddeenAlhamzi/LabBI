<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('requested');
            $table->timestamp('ordered_at');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['status', 'ordered_at']);
        });

        Schema::create('test_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('requested');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_number')->unique();
            $table->foreignId('test_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('awaiting');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
        Schema::dropIfExists('test_order_items');
        Schema::dropIfExists('test_orders');
    }
};
