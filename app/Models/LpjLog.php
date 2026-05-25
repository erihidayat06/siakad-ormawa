<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpjLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'lpj_id',
        'user_id',
        'action',
        'notes',
        'file_result',
    ];

    /**
     * Log ini dimiliki oleh satu data LPJ
     */
    public function lpj(): BelongsTo
    {
        return $this->belongsTo(Lpj::class);
    }

    /**
     * Log ini dicatat oleh siapa (User/Aktor)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
