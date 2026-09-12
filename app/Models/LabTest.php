<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Laboratory test catalog item (table: tests).
 */
class LabTest extends Model
{
    protected $table = 'tests';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'unit',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function referenceRanges(): HasMany
    {
        return $this->hasMany(TestReferenceRange::class, 'test_id');
    }
}
