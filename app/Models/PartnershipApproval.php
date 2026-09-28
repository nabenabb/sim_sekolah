<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipApproval extends Model
{
    protected $fillable = ['document_id', 'tahap', 'status', 'approved_by', 'tanggal'];

    // Role yang berhak menyetujui tiap tahap - dipakai controller & view
    public const TAHAP_ROLE = [
        'Verifikasi Humas' => 'Waka Humas',
        'Verifikasi Kaprog' => 'Kepala Program Keahlian',
        'Persetujuan Kepala Sekolah' => 'Kepala Sekolah',
    ];

    public function document()
    {
        return $this->belongsTo(PartnershipDocument::class, 'document_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
