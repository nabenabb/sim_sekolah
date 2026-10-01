<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndustryPartner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama_perusahaan', 'alamat_pusat', 'bidang_usaha',
        'kontak_person', 'no_telepon', 'status', 'program_keahlian_id',
    ];

    public function programKeahlian()
    {
        return $this->belongsTo(ProgramKeahlian::class, 'program_keahlian_id');
    }

    public function documents()
    {
        return $this->hasMany(PartnershipDocument::class, 'partner_id');
    }

    // Dokumen kerja sama yang paling relevan buat nampilin status di tabel
    // (yang disetujui & belum kadaluarsa, terbaru duluan)
    public function latestActiveDocument()
    {
        return $this->hasOne(PartnershipDocument::class, 'partner_id')
            ->where('status', 'disetujui')
            ->latest('berlaku_selesai');
    }
}
