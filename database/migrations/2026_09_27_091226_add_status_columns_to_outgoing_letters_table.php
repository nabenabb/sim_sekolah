<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->enum('status', ['draf', 'menunggu_ttd', 'terkirim'])->default('draf')->after('perihal');
            $table->text('isi_ringkas')->nullable()->after('perihal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn(['status', 'isi_ringkas']);
        });
    }
};
