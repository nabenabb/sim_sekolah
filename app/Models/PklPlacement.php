<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PklPlacement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id', 'partner_id', 'pembimbing_id', 'pembimbing_industri',
        'tanggal_mulai', 'tanggal_selesai', 'status', 'nilai', 'surat_permohonan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'nilai' => 'float',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function partner()
    {
        return $this->belongsTo(IndustryPartner::class, 'partner_id');
    }

    public function pembimbing()
    {
        return $this->belongsTo(User::class, 'pembimbing_id');
    }

    public function certificate()
    {
        return $this->hasOne(Certificate::class, 'placement_id');
    }

    // Skala predikat (asumsi, silakan sesuaikan dengan aturan sekolah)
    public function predikat(): ?string
    {
        if ($this->nilai === null) {
            return null;
        }

        return match (true) {
            $this->nilai >= 85 => 'Sangat Baik',
            $this->nilai >= 75 => 'Baik',
            $this->nilai >= 60 => 'Cukup',
            default => 'Kurang',
        };
    }

    public function periodeText(): string
    {
        return $this->tanggal_mulai->translatedFormat('d M') . ' – ' . $this->tanggal_selesai->translatedFormat('d M Y');
    }
}
