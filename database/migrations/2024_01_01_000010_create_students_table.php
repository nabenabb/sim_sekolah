<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('nis', 20)->unique();
            $table->date('tanggal_lahir')->nullable();
            $table->foreignId('program_keahlian_id')
                ->constrained('program_keahlian');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
