<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    protected $fillable = [
        'user_id',
        'license_number',
        'specialty',
        'department_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TestOrder::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'primary_doctor_id');
    }
}
