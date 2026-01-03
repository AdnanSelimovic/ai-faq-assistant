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
        Schema::create('kb_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('kb_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->longText('content');
            $table->char('content_hash', 64);
            $table->json('embedding')->nullable();
            $table->unsignedInteger('token_count')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'chunk_index']);
            $table->index('document_id');
            $table->index('content_hash');
        });

        if (DB::getDriverName() === 'mysql') {
            try {
                Schema::table('kb_chunks', function (Blueprint $table) {
                    $table->fullText('content');
                });
            } catch (\Throwable $e) {
                // Ignore if fulltext indexes are unsupported.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_chunks');
    }
};
