<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partnership_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')
                ->constrained('partnership_documents');
            // mis. Verifikasi Humas, Verifikasi Kaprog, Persetujuan Kepsek
            $table->string('tahap', 100);
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak']);
            $table->foreignId('approved_by')
                ->constrained('users');
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partnership_approvals');
    }
};
