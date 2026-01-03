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
        Schema::create('kb_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('source_type', 50);
            $table->string('source_ref', 2048)->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('source_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kb_documents');
    }
};
