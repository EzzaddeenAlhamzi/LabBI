<?php

namespace App\Providers;

use App\Contracts\AnalyzerIngestionPort;
use App\Contracts\ResultApprovalContract;
use App\Domain\Analyzer\NullAnalyzerIngestionAdapter;
use App\Services\ResultApprovalService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResultApprovalContract::class, ResultApprovalService::class);
        $this->app->singleton(AnalyzerIngestionPort::class, NullAnalyzerIngestionAdapter::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
