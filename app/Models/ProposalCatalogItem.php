<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProposalCatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_catalog_id',
        'proposal_item_id',
        'title',
        'specification',
        'quantity',
        'unit',
        'brand',
        'position',
        'image_path',
        'image_original_name',
        'image_mime',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'position' => 'integer',
    ];

    protected $hidden = [
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    public function catalog()
    {
        return $this->belongsTo(ProposalCatalog::class, 'proposal_catalog_id');
    }

    public function proposalItem()
    {
        return $this->belongsTo(ProposalItem::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }
}
