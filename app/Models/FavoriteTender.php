<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteTender extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'favorite_tenders';

    protected $fillable = [
        'tender_id',
        'user_id',
    ];

    public function tenders(){
        return $this->belongsTo(Tender::class);
    }

    public function users(){
        return $this->belongsTo(User::class);
    }

}
