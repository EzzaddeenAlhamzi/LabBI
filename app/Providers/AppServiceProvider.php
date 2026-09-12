<?php

namespace App\Providers;

use App\Contracts\AnalyzerIngestionPort;
use App\Contracts\PdfReportGenerator;
use App\Contracts\ResultApprovalContract;
use App\Contracts\WhatsAppGateway;
use App\Domain\Analyzer\NullAnalyzerIngestionAdapter;
use App\Domain\WhatsApp\MockWhatsAppGateway;
use App\Services\PdfReportService;
use App\Services\ResultApprovalService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResultApprovalContract::class, ResultApprovalService::class);
        $this->app->singleton(AnalyzerIngestionPort::class, NullAnalyzerIngestionAdapter::class);
        $this->app->singleton(WhatsAppGateway::class, MockWhatsAppGateway::class);
        $this->app->singleton(PdfReportGenerator::class, PdfReportService::class);
        $this->app->singleton(\App\Services\NotificationOrchestrator::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
