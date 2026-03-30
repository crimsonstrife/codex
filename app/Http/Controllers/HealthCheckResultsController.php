<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Spatie\Health\ResultStores\ResultStore;
use Throwable;

class HealthCheckResultsController extends Controller
{
    public function __invoke(ResultStore $resultStore): View
    {
        try {
            $checkResults = $resultStore->latestResults();
        } catch (Throwable) {
            $checkResults = null;
        }

        return view('pages.status', [
            'checkResults' => $checkResults,
        ]);
    }
}
