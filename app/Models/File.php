<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'files';

    protected $fillable = [
        'description',
        'filename',
        'path',
        'expiration_date',
        'category_id',
        'user_id',
    ];

    public function getPathAttribute()
    {
        if (isset($this->attributes['path'])) {
            return url('storage/' . $this->attributes['path']);
        }
        return null;
    }
    
    public function category(){
        return $this->belongsTo(Category::class);
    }

}
