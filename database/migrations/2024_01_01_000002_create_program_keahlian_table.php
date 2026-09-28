<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_keahlian', function (Blueprint $table) {
            $table->id();
            $table->string('nama_jurusan', 100); // contoh: RPL, TKJ, dll.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_keahlian');
    }
};
