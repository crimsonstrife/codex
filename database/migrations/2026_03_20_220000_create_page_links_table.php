<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks [[Page Title]] wiki-style cross-references between pages.
 *
 * Populated / refreshed every time a page is saved (PageLinkResolver::sync()).
 * Used for:
 *   - Resolving [[...]] at render time (PageLinkResolver::render())
 *   - Future "What Links Here" / backlink features (Phase 4.3)
 *
 * One row per unique (source, target) pair — anchor_text stores the title text
 * of the first/primary occurrence of the link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('source_page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->foreignUuid('target_page_id')
                ->constrained('pages')
                ->cascadeOnDelete();

            $table->string('anchor_text');

            $table->timestamps();

            // One link record per source→target pair
            $table->unique(['source_page_id', 'target_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_links');
    }
};
