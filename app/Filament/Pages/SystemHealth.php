<?php

namespace App\Filament\Pages;

use App\Support\HealthCheckResultPresenter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Health;
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

    protected string $view = 'filament.pages.health-check-results';

    public ?string $lastRanLabel = null;

    public bool $staleResults = false;

    public string $healthAssets = '';

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $presentedResults = [];

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

        $finishedAt = $results?->finishedAt ? Carbon::parse($results->finishedAt) : null;

        $this->lastRanLabel = $finishedAt?->diffForHumans();
        $this->staleResults = $finishedAt?->diffInMinutes() > 5;
        $this->healthAssets = app(Health::class)->assets()->toHtml();
        $this->presentedResults = HealthCheckResultPresenter::present($results?->storedCheckResults ?? []);
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
