<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lpj extends Model
{
    protected $fillable = [
        'proposal_id',
        'user_id',
        'title',
        'original_file',
        'current_file',
        'status',
        'current_step',
    ];

    // Relasi balik ke Proposal
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    // Relasi ke pembuat (Mahasiswa)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Tambahkan ini di Model Proposal.php DAN Model Lpj.php
    public function logs()
    {
        return $this->morphMany(ProposalLog::class, 'loggable');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Jika login sebagai mahasiswa, hanya bisa lihat LPJ miliknya sendiri
        if (auth()->user()->role === 'mahasiswa') {
            return $query->where('user_id', auth()->id());
        }

        // Pejabat (Kaprodi, WD3, WD2) bisa melihat semua LPJ yang masuk ke fakultas
        return $query;
    }

    public function Lpjlogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LpjLog::class, 'lpj_id');
    }
}
