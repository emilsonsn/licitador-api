<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalTrackingItemRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_tracking_item_id',
        'position',
        'company',
        'brand',
        'price',
    ];

    protected $casts = [
        'position' => 'integer',
        'price' => 'decimal:4',
    ];

    public function trackingItem()
    {
        return $this->belongsTo(ProposalTrackingItem::class, 'proposal_tracking_item_id');
    }
}
