<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalCatalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_id',
        'user_id',
        'company_id',
        'title',
        'subtitle',
        'general_notes',
        'organ_name',
        'organ_state',
        'purchase_number',
        'process_number',
        'receipt_date',
        'opening_date',
        'company_snapshot',
        'last_updated_by',
        'generated_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'opening_date' => 'date',
        'company_snapshot' => 'array',
        'generated_at' => 'datetime',
    ];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(ProposalCatalogItem::class)->orderBy('position');
    }
}
