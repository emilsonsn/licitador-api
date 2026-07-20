<?php

namespace App\Models;

use App\Enums\ProposalTrackingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'discount_percentage',
        'status',
        'last_updated_by',
        'finished_at',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:4',
        'status' => ProposalTrackingStatus::class,
        'finished_at' => 'datetime',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function items()
    {
        return $this->hasMany(ProposalTrackingItem::class);
    }

    public function lastUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
}
