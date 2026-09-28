<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disposition extends Model
{
     protected $fillable = ['letter_id', 'instruksi', 'status', 'assigned_to'];

    public function letter()
    {
        return $this->belongsTo(IncomingLetter::class, 'letter_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
