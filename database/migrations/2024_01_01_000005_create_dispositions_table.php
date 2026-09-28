<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')
                ->constrained('incoming_letters');
            // Tanggapan dan saran / Proses lebih lanjut / Koordinasi-konfirmasi
            $table->string('instruksi', 255);
            $table->enum('status', ['belum ditindaklanjuti', 'diproses', 'selesai']);
            $table->foreignId('assigned_to')
                ->constrained('users'); // tujuan disposisi; 1 baris = 1 tujuan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositions');
    }
};
