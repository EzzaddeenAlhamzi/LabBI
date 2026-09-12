<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestReferenceRange extends Model
{
    protected $fillable = [
        'test_id',
        'normal_low',
        'normal_high',
        'critical_low',
        'critical_high',
        'sex',
        'age_min',
        'age_max',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'normal_low' => 'decimal:4',
            'normal_high' => 'decimal:4',
            'critical_low' => 'decimal:4',
            'critical_high' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }
}
