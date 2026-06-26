<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'item',
        'quantity',
        'unit',
        'specification',
        'brand',
        'unit_price',
        'total_value',
        'source_payload',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_value' => 'decimal:2',
        'source_payload' => 'array',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }
}
