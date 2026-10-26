<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Disposition extends Model
{
    use SoftDeletes;

    // Role yang bisa dipilih sebagai tujuan disposisi (satu sumber untuk form, rute, dan menu)
    public const ROLE_PENERIMA = ['Waka Kurikulum', 'Waka Kesiswaan', 'Waka Sapras', 'Waka Humas'];

    protected $fillable = ['letter_id', 'instruksi', 'status', 'assigned_to'];

    public function letter()
    {
        return $this->belongsTo(IncomingLetter::class, 'letter_id')->withTrashed();
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
