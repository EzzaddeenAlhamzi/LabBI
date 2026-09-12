<?php

namespace App\Http\Controllers;

use App\Models\GeneratedReport;
use App\Services\AuditLogger;
use App\Services\PdfReportService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        GeneratedReport $report,
        PdfReportService $pdf,
        AuditLogger $audit,
    ): StreamedResponse {
        $report->loadMissing('order');

        try {
            $pdf->assertCanDownload($report, $request->user());
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        if (! Storage::disk($report->storage_disk)->exists($report->storage_key)) {
            abort(404);
        }

        $audit->log('pdf.downloaded', $report, null, [
            'report_id' => $report->id,
        ], $request->user()->id);

        return Storage::disk($report->storage_disk)->download(
            $report->storage_key,
            'labbi-report-'.$report->id.'.pdf'
        );
    }
}
