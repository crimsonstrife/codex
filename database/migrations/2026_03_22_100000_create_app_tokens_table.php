<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * App-level tokens for machine-to-machine API access.
 * These are not tied to any user — they represent a trusted external application
 * (e.g. Forge calling the Codex API for workspace/page lookups).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('token', 64)->unique(); // SHA-256 of the raw token
            $table->text('abilities'); // JSON array; MySQL < 8.0 forbids defaults on JSON/TEXT
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_tokens');
    }
};
