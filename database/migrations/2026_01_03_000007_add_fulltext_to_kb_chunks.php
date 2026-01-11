<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            Schema::table('kb_chunks', function (Blueprint $table) {
                $table->fullText('content');
            });
        } catch (\Throwable $e) {
            // Ignore if fulltext index already exists or is unsupported.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        try {
            Schema::table('kb_chunks', function (Blueprint $table) {
                $table->dropFullText(['content']);
            });
        } catch (\Throwable $e) {
            // Ignore if fulltext index is missing.
        }
    }
};
