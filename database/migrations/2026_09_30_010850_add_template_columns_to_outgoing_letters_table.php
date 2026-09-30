<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->string('jenis_template', 20)->default('umum')->after('perihal');
            $table->json('data_template')->nullable()->after('jenis_template');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn(['jenis_template', 'data_template']);
        });
    }
};
