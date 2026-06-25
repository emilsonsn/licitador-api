<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'companies';

    protected $fillable = [
        'user_id',
        'cnpj',
        'corporate_reason',
        'fantasy_name',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zipcode',
        'phone',
        'email',
        'legal_representative_name',
        'legal_representative_rg',
        'legal_representative_cpf',
        'bank',
        'agency',
        'checking_account',
        'logo',
    ];

    public function getLogoAttribute()
    {
        if (! isset($this->attributes['logo'])) {
            return null;
        }

        if (filter_var($this->attributes['logo'], FILTER_VALIDATE_URL)) {
            return $this->attributes['logo'];
        }

        return url('storage/' . $this->attributes['logo']);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
