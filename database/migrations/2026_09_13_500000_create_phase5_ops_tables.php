<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->timestamp('taken_at')->useCurrent();
            $table->string('status', 32); // pending|success|failed|restored
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('disk', 64)->default('local');
            $table->string('storage_path')->nullable();
            $table->string('trigger', 32)->default('manual'); // scheduled|manual|drill
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('error_summary')->nullable();
            $table->json('manifest')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'taken_at']);
            $table->index('taken_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
