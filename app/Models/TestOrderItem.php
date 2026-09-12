<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestOrderItem extends Model
{
    protected $fillable = [
        'test_order_id',
        'test_id',
        'status',
        'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TestOrder::class, 'test_order_id');
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'test_id');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }
}
