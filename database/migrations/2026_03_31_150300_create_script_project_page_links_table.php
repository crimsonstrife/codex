<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_project_page_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('script_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('page_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('notes');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['script_project_id', 'page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_project_page_links');
    }
};
