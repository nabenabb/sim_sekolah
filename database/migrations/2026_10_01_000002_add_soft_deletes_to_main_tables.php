<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'incoming_letters',
            'dispositions',
            'outgoing_letters',
            'industry_partners',
            'partnership_documents',
            'pkl_placements',
            'certificates',
            'students',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->softDeletes();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'incoming_letters',
            'dispositions',
            'outgoing_letters',
            'industry_partners',
            'partnership_documents',
            'pkl_placements',
            'certificates',
            'students',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }
};
