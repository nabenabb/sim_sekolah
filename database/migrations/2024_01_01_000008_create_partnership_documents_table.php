<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partnership_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')
                ->constrained('industry_partners');
            $table->enum('jenis_dokumen', ['MoU', 'PKS', 'MoA']);
            $table->date('berlaku_mulai');
            $table->date('berlaku_selesai'); // dipakai utk reminder otomatis
            $table->string('file_dokumen', 255)->nullable();
            $table->enum('status', ['diajukan', 'diverifikasi', 'disetujui', 'ditolak', 'kadaluarsa']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_documents');
    }
};
