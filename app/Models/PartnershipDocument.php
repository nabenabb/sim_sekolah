<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipDocument extends Model
{
    protected $fillable = [
        'partner_id', 'jenis_dokumen', 'ruang_lingkup', 'berlaku_mulai',
        'berlaku_selesai', 'file_dokumen', 'status',
    ];

    protected function casts(): array
    {
        return [
            'berlaku_mulai' => 'date',
            'berlaku_selesai' => 'date',
        ];
    }

    public function partner()
    {
        return $this->belongsTo(IndustryPartner::class, 'partner_id');
    }

    public function approvals()
    {
        return $this->hasMany(PartnershipApproval::class, 'document_id')->orderBy('id');
    }
}
