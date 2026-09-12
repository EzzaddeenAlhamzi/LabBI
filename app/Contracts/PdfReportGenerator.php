<?php

namespace App\Contracts;

use App\Models\Result;
use App\Models\User;

/**
 * PDF report generation port — presentation separated from domain orchestration.
 */
interface PdfReportGenerator
{
    /**
     * Generate (or return existing idempotent) PDF for an approved result only.
     *
     * @return \App\Models\GeneratedReport
     */
    public function generateForApprovedResult(Result $result, ?User $actor = null);
}
