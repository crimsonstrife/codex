<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('script_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->string('logline')->nullable();
            $table->text('synopsis')->nullable();
            $table->json('document')->nullable();
            $table->unsignedInteger('revision_number')->default(1);
            $table->string('change_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_revisions');
    }
};
