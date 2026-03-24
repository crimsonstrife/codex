<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug');
            $table->string('color', 7)->nullable();
            $table->foreignUuid('workspace_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['slug', 'workspace_id']);
        });

        Schema::create('categorizables', function (Blueprint $table) {
            $table->foreignUuid('category_id')->constrained()->cascadeOnDelete();
            $table->uuidMorphs('categorizable');
            $table->primary(['category_id', 'categorizable_id', 'categorizable_type'], 'categorizables_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorizables');
        Schema::dropIfExists('categories');
    }
};
