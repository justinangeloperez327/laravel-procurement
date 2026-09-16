<?php

namespace App\Domain\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementMethod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'effective_from',
        'effective_until',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
            'sort_order' => 'integer',
        ];
    }
}
