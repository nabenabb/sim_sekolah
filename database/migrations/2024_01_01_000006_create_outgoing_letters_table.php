<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->string('no_surat', 50)->unique(); // auto-generate sesuai format sekolah
            $table->date('tanggal');
            $table->string('tujuan', 150);
            $table->string('perihal', 255);
            $table->string('file_path', 255)->nullable();
            $table->foreignId('ref_incoming_letter_id')
                ->nullable()
                ->constrained('incoming_letters'); // opsional, boleh mandiri
            $table->foreignId('created_by')
                ->constrained('users');
            $table->foreignId('signed_by')
                ->nullable()
                ->constrained('users'); // Kepala Sekolah
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
