<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'tenders';

    protected $fillable = [
        'id_licitacao',
        'value',
        'modality',
        'modality_id',
        'status',
        'year_purchase',
        'number_purchase',
        'sequential_purchase',
        'organ_cnpj',
        'organ_name',
        'uf',
        'city',
        'city_code',
        'description',
        'object',
        'instrument_name',
        'observations',
        'origin_url',
        'process',
        'bid_opening_date',
        'proposal_closing_date',
        'publication_date',
        'api_origin',
        'update_date',
    ];

    public function favorites(){
        return $this->hasMany(FavoriteTender::class);
    }

    public function items(){
        return $this->hasMany(Item::class);
    }

    public function notes(){
        return $this->hasMany(Note::class);
    }
}
