<?php

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'medical_file_number',
        'full_name',
        'date_of_birth',
        'sex',
        'phone',
        'national_id',
        'primary_doctor_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            // AES-256 via APP_KEY (Laravel encrypted cast). Not used for search indexes.
            'national_id' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TestOrder::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ResultAttachment::class);
    }

    public function criticalAlerts(): HasMany
    {
        return $this->hasMany(CriticalAlert::class);
    }
}
