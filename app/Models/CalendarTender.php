<?php

namespace App\Models;

use App\Enums\CalendarTenderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarTender extends Model
{
    use HasFactory;

    public $table = 'calendar_tenders';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'tender_id',
        'user_id',
        'status',
    ];

    protected $casts = [
        'status' => CalendarTenderStatus::class,
    ];

    public function tender()
    {
        return $this->belongsTo(Tender::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
