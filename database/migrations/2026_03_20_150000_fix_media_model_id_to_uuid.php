<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The vendor-published create_media_table migration uses morphs('model') which
 * creates model_id as unsignedBigInteger. All Codex models use UUID primary
 * keys, so we need model_id to be char(36).
 *
 * Strategy (non-destructive):
 * - Fresh install  → table doesn't exist yet; create it correctly with uuidMorphs.
 * - Existing table → drop the integer-based morph columns and recreate them as
 *   UUID-based in-place, preserving any rows already present.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();

                $table->uuidMorphs('model');
                $table->uuid()->nullable()->unique();
                $table->string('collection_name');
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk');
                $table->string('conversions_disk')->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations');
                $table->json('custom_properties');
                $table->json('generated_conversions');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable()->index();

                $table->nullableTimestamps();
            });

            return;
        }

        // Table already exists with integer morphs — switch to UUID morphs in-place.
        Schema::table('media', function (Blueprint $table) {
            $table->dropMorphs('model');
            $table->uuidMorphs('model');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('media')) {
            return;
        }

        Schema::table('media', function (Blueprint $table) {
            $table->dropMorphs('model');
            $table->morphs('model');
        });
    }
};
