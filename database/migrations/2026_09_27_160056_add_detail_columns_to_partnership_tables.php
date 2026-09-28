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
       Schema::table('industry_partners', function (Blueprint $table) {
            $table->string('no_telepon', 30)->nullable()->after('kontak_person');
        });

        Schema::table('partnership_documents', function (Blueprint $table) {
            $table->string('ruang_lingkup', 255)->nullable()->after('jenis_dokumen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('industry_partners', function (Blueprint $table) {
            $table->dropColumn('no_telepon');
        });
        Schema::table('partnership_documents', function (Blueprint $table) {
            $table->dropColumn('ruang_lingkup');
        });
    
    }
};
