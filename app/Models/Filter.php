<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'filters';

    protected $fillable = [
        'user_id',
        'object',
        'uf',
        'city',
        'modality_ids',
        'update_date_start',
        'update_date_end',
        'organ_cnpj',
        'organ_name',
        'process',
        'observations',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }}
