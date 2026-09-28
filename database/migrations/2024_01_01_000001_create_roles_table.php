<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // Kepala Sekolah, Waka Kurikulum, Waka Kesiswaan, Waka Sapras,
            // Waka Humas, Pembina OSIS, Koordinator TU, Bendahara Sekolah/BOS,
            // Bendahara SPP, Kepala Program Keahlian, Guru, Siswa, Admin
            $table->string('name', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
