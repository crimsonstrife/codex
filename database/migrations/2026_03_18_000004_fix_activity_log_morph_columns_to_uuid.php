<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The initial activity_log migration used nullableMorphs() which generates
 * bigint unsigned IDs. Because both subjects (Page) and causers (User) use
 * UUID primary keys the causer_id / subject_id columns must be char(36).
 * This migration drops and recreates the table with nullableUuidMorphs().
 */
return new class extends Migration
{
    private function logConn(): ?string
    {
        return config('activitylog.database_connection') ?: null;
    }

    private function logTable(): string
    {
        return config('activitylog.table_name', 'activity_log');
    }

    public function up(): void
    {
        Schema::connection($this->logConn())->dropIfExists($this->logTable());

        Schema::connection($this->logConn())->create($this->logTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableUuidMorphs('subject', 'subject');   // subject_id char(36), subject_type varchar(255)
            $table->string('event')->nullable();
            $table->nullableUuidMorphs('causer', 'causer');     // causer_id  char(36), causer_type  varchar(255)
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->logConn())->dropIfExists($this->logTable());

        // Restore the original (bigint) schema on rollback
        Schema::connection($this->logConn())->create($this->logTable(), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }
};
