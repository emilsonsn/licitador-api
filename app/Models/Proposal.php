<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'tender_id',
        'title',
        'organ_name',
        'organ_state',
        'purchase_number',
        'process_number',
        'receipt_date',
        'opening_date',
        'declarations',
        'city',
        'proposal_date',
        'responsible_name',
        'responsible_rg',
        'responsible_cpf',
        'total_value',
        'status',
        'company_snapshot',
        'tender_snapshot',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'opening_date' => 'date',
        'proposal_date' => 'date',
        'total_value' => 'decimal:2',
        'status' => ProposalStatus::class,
        'company_snapshot' => 'array',
        'tender_snapshot' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function items()
    {
        return $this->hasMany(ProposalItem::class);
    }

    public function tracking()
    {
        return $this->hasOne(ProposalTracking::class);
    }

    public function catalog()
    {
        return $this->hasOne(ProposalCatalog::class);
    }
}
