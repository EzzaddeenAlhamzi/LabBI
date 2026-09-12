<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('license_number')->nullable();
            $table->string('specialty')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('laboratory_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('medical_file_number')->unique();
            $table->string('full_name');
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable();
            $table->foreignId('primary_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['full_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
        Schema::dropIfExists('laboratory_staff');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('departments');
    }
};
