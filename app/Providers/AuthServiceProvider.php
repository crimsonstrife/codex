<?php

namespace App\Providers;

use App\Models\Diagram;
use App\Models\Page;
use App\Models\ScriptProject;
use App\Models\Workspace;
use App\Policies\DiagramPolicy;
use App\Policies\PagePolicy;
use App\Policies\ScriptProjectPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Page::class => PagePolicy::class,
        Diagram::class => DiagramPolicy::class,
        ScriptProject::class => ScriptProjectPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
