<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificate extends Model
{
    use SoftDeletes;

    protected $fillable = ['placement_id', 'nomor_sertifikat', 'tanggal_terbit', 'file_path', 'qr_code'];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
        ];
    }

    public function placement()
    {
        return $this->belongsTo(PklPlacement::class, 'placement_id');
    }
}
