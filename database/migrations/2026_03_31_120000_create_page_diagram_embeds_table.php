<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which diagrams are embedded inside which pages.
 *
 * Populated / refreshed every time a page is saved or restored
 * (DiagramEmbedRenderer::sync()).
 *
 * Used for:
 *   - Showing "used in pages" on a diagram
 *   - Preventing deletion of diagrams that are still embedded
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_diagram_embeds', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->foreignUuid('diagram_id')
                ->constrained('diagrams')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['page_id', 'diagram_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_diagram_embeds');
    }
};
