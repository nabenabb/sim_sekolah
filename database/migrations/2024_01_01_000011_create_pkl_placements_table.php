<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkl_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students');
            $table->foreignId('partner_id')
                ->constrained('industry_partners');
            $table->foreignId('pembimbing_id')
                ->constrained('users'); // pembimbing sekolah
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['berjalan', 'selesai', 'dibatalkan']);
            $table->float('nilai')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkl_placements');
    }
};
