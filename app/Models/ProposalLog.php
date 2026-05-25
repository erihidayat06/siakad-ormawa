<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProposalLog extends Model
{
    protected $fillable = [
        'user_id',
        'loggable_id',
        'loggable_type',
        'action',
        'notes',
        'file_result',


    ];

    public function loggable()
    {
        // Ini memungkinkan log terhubung ke Proposal atau Lpj secara otomatis
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
