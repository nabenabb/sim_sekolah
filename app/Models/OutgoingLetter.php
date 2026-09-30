<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutgoingLetter extends Model
{
    protected $fillable = [
        'no_surat', 'tanggal', 'tujuan', 'perihal', 'isi_ringkas', 'jenis_template', 'data_template',
        'status', 'file_path', 'ref_incoming_letter_id', 'created_by', 'signed_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'data_template' => 'array',
        ];
    }

    public function refLetter()
    {
        return $this->belongsTo(IncomingLetter::class, 'ref_incoming_letter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function signer()
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
