<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PROGRAMS = [
        'Desain Komunikasi Visual (DKV)',
        'Animasi',
        'Produk Siaran dan Program Televisi (PSPT)',
        'Pengembangan Gim',
    ];

    public function up(): void
    {
        $timestamp = now();

        foreach (self::PROGRAMS as $program) {
            $exists = DB::table('program_keahlian')
                ->where('nama_jurusan', $program)
                ->exists();

            if (! $exists) {
                DB::table('program_keahlian')->insert([
                    'nama_jurusan' => $program,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep these master values if user, student, or partner records reference them.
    }
};
