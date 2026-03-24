<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_comments', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('content');
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('page_comments', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolved_at', 'resolved_by']);
        });
    }
};
