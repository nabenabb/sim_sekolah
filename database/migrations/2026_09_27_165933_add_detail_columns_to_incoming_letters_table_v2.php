<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            $table->string('nomor_surat', 50)->nullable()->after('pengirim');
            $table->date('tanggal_surat')->nullable()->after('nomor_surat');
            $table->enum('sifat', ['Biasa', 'Segera', 'Sangat Segera', 'Rahasia'])->default('Biasa')->after('tanggal_terima');
        });
    }

    public function down(): void
    {
        Schema::table('incoming_letters', function (Blueprint $table) {
            $table->dropColumn(['nomor_surat', 'tanggal_surat', 'sifat']);
        });
    }
};