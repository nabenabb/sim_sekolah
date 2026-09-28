<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingLetter extends Model
{
    protected $fillable = [
        'no_agenda', 'pengirim', 'nomor_surat', 'tanggal_surat',
        'tanggal_terima', 'sifat', 'perihal', 'file_path', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'tanggal_terima' => 'date',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispositions()
    {
        return $this->hasMany(Disposition::class, 'letter_id');
    }
}
