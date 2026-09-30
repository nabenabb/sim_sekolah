<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partnership_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->change();
            $table->date('tanggal')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('partnership_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable(false)->change();
            $table->date('tanggal')->nullable(false)->change();
        });
    }
};