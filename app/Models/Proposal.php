<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'original_file',
        'current_file',
        'status',
        'current_step',
        'payment_proof',
    ];

    // Relasi balik ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke LPJ (Siklus 2)
    public function lpj()
    {
        return $this->hasOne(Lpj::class);
    }

    // Tambahkan ini di Model Proposal.php DAN Model Lpj.php
    public function logs()
    {
        return $this->morphMany(ProposalLog::class, 'loggable');
    }
}
