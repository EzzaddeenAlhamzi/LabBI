<?php

namespace App\Jobs;

use App\Contracts\PdfReportGenerator;
use App\Models\Result;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateResultPdfJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $resultId,
        public ?int $actorId = null,
    ) {}

    public function handle(PdfReportGenerator $pdf): void
    {
        $result = Result::query()->find($this->resultId);
        if (! $result) {
            return;
        }

        if ($result->status !== Result::STATUS_APPROVED) {
            Log::info('pdf.skipped_unapproved', ['result_id' => $this->resultId]);

            return;
        }

        $actor = $this->actorId ? User::query()->find($this->actorId) : null;
        $pdf->generateForApprovedResult($result, $actor);
    }
}
