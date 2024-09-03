<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'notes';

    protected $fillable = [
        'tender_id',
        'note',
    ];

    public function tender(){
        return $this->belongsTo(Tender::class);
    }
}
