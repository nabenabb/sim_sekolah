<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_id')
                ->unique() // 1 penempatan = 1 sertifikat
                ->constrained('pkl_placements');
            $table->string('nomor_sertifikat', 50)->unique(); // auto-generate
            $table->date('tanggal_terbit');
            $table->string('file_path', 255); // PDF
            $table->string('qr_code', 255)->unique(); // untuk cek keaslian
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
