<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('no_agenda', 30)->unique(); // auto-generate
            $table->date('tanggal_terima');
            $table->string('pengirim', 150);
            $table->string('perihal', 255);
            $table->string('file_path', 255)->nullable(); // hasil scan/upload
            $table->foreignId('created_by')
                ->constrained('users'); // staf TU
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
