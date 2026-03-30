<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $tableName = config('settings.repositories.database.table') ?? 'settings';

        if (! Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table): void {
                $table->id();
                $table->string('group');
                $table->string('name');
                $table->boolean('locked')->default(false);
                $table->json('payload');
                $table->timestamps();
                $table->unique(['group', 'name']);
            });
        }

        $this->migrator->add('auth.allowRegistration', true);
        $this->migrator->add('codex.defaultPageContentType', config('codex.editor.default_content_type', 'markdown'));
        $this->migrator->add('codex.drawioUrl', config('codex.diagrams.drawio_url', 'https://embed.diagrams.net'));
        $this->migrator->add('forge.enabled', (bool) config('codex.forge.enabled', false));
        $this->migrator->add('forge.url', (string) config('codex.forge.url', ''));
        $this->migrator->add('forge.disableTlsVerification', (bool) config('codex.forge.disable_tls_verification', false));
    }
};
