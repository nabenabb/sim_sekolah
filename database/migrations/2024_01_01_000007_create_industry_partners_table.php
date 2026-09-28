<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_partners', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan', 150);
            $table->string('alamat_pusat', 255);
            $table->string('bidang_usaha', 100);
            $table->string('kontak_person', 100);
            $table->enum('status', ['aktif', 'tidak aktif']);
            $table->foreignId('program_keahlian_id')
                ->constrained('program_keahlian'); // jurusan yang relevan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industry_partners');
    }
};
