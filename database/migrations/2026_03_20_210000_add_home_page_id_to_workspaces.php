<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add home_page_id to workspaces so a workspace owner can designate one page
 * as the "landing page" for the workspace.  Visitors to workspaces/{workspace}
 * are redirected to that page; the full hub view is still accessible via
 * workspaces/{workspace}?hub=1.
 *
 * On delete of the referenced page, the FK is set to NULL automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignUuid('home_page_id')
                ->nullable()
                ->after('icon')
                ->constrained('pages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['home_page_id']);
            $table->dropColumn('home_page_id');
        });
    }
};
