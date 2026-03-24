<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('page_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('parent_id')->nullable();
            $table->text('content');
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('page_comments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_comments');
    }
};
