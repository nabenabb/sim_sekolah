<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramKeahlian extends Model
{
    protected $table = 'program_keahlian';

    protected $fillable = ['nama_jurusan'];

    public function partners()
    {
        return $this->hasMany(IndustryPartner::class, 'program_keahlian_id');
    }
}
