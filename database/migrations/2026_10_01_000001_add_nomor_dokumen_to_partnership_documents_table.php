<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partnership_documents', function (Blueprint $table) {
            $table->string('nomor_dokumen', 100)->nullable()->after('jenis_dokumen');
        });
    }

    public function down(): void
    {
        Schema::table('partnership_documents', function (Blueprint $table) {
            $table->dropColumn('nomor_dokumen');
        });
    }
};
