<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\ResultStores\ResultStore;
use Throwable;

class SystemHealth extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Health';

    protected static ?string $title = 'System Health';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'system-health';

    protected string $view = 'filament.pages.system-health';

    public ?string $finishedAt = null;

    public bool $allChecksOk = false;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $checkResults = [];

    public function mount(): void
    {
        $this->loadResults();
    }

    public function loadResults(): void
    {
        try {
            $results = app(ResultStore::class)->latestResults();
        } catch (Throwable) {
            $results = null;
        }

        $this->finishedAt = $results?->finishedAt?->format('M j, Y g:i:s A');
        $this->allChecksOk = $results?->allChecksOk() ?? false;
        $this->checkResults = $results?->storedCheckResults
            ->map(fn ($result): array => [
                'name' => $result->name,
                'label' => $result->label,
                'status' => $result->status,
                'shortSummary' => $result->shortSummary,
                'notificationMessage' => $result->notificationMessage,
                'meta' => $result->meta,
            ])
            ->values()
            ->all() ?? [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Run Checks')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    app()->terminating(fn (): int => Artisan::call(RunHealthChecksCommand::class));

                    Notification::make()
                        ->success()
                        ->title('Health checks queued')
                        ->body('A fresh health run will start after this response. Stored results on this page refresh automatically.')
                        ->send();

                    $this->loadResults();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('health.view') ?? false;
    }
}
