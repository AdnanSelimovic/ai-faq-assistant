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
        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route', 100);
            $table->string('key', 100);
            $table->string('request_hash', 64)->nullable();
            $table->json('response_json');
            $table->unsignedInteger('status_code');
            $table->timestamps();

            $table->unique(['user_id', 'route', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
