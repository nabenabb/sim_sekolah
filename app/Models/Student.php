<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = ['nama', 'nis', 'tanggal_lahir', 'program_keahlian_id'];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function programKeahlian()
    {
        return $this->belongsTo(ProgramKeahlian::class, 'program_keahlian_id');
    }

    public function placements()
    {
        return $this->hasMany(PklPlacement::class, 'student_id');
    }
}
