<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('script_entities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('script_project_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->json('aliases')->nullable();
            $table->text('notes')->nullable();
            $table->string('hierarchy_text')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['script_project_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_entities');
    }
};
