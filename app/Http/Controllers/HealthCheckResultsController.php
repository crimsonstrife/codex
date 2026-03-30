<?php

namespace App\Http\Controllers;

use App\Support\HealthCheckResultPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Health;
use Spatie\Health\ResultStores\ResultStore;
use Throwable;

class HealthCheckResultsController extends Controller
{
    public function __invoke(Request $request, ResultStore $resultStore, Health $health): View
    {
        if ($request->has('fresh')) {
            Artisan::call(RunHealthChecksCommand::class);
        }

        try {
            $checkResults = $resultStore->latestResults();
        } catch (Throwable) {
            $checkResults = null;
        }

        $finishedAt = $checkResults?->finishedAt ? Carbon::parse($checkResults->finishedAt) : null;

        return view('pages.status', [
            'presentedResults' => HealthCheckResultPresenter::present($checkResults?->storedCheckResults ?? []),
            'lastRanLabel' => $finishedAt?->diffForHumans(),
            'staleResults' => $finishedAt?->diffInMinutes() > 5,
            'healthAssets' => $health->assets(),
        ]);
    }
}
