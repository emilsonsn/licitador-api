<?php

namespace App\Models;

use App\Enums\ProposalTrackingItemResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalTrackingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_tracking_id',
        'proposal_item_id',
        'result',
        'minimum_unit_price',
        'classified_at',
        'classified_by',
    ];

    protected $casts = [
        'result' => ProposalTrackingItemResult::class,
        'minimum_unit_price' => 'decimal:4',
        'classified_at' => 'datetime',
    ];

    public function tracking()
    {
        return $this->belongsTo(ProposalTracking::class, 'proposal_tracking_id');
    }

    public function proposalItem()
    {
        return $this->belongsTo(ProposalItem::class);
    }

    public function classifiedBy()
    {
        return $this->belongsTo(User::class, 'classified_by');
    }

    public function rankings()
    {
        return $this->hasMany(ProposalTrackingItemRanking::class);
    }
}
