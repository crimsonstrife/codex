<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagrams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->foreignUuid('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->longText('diagram_data')->nullable(); // XML/JSON for draw.io
            $table->string('diagram_type')->default('drawio'); // drawio | mindmap | flowchart
            $table->boolean('is_published')->default(false);
            $table->string('thumbnail_url')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['workspace_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagrams');
    }
};
