<?php

namespace App\Contracts;

/**
 * Future analyzer / HL7 / ASTM / vendor ingestion port.
 * V1: manual entry only — do not implement real device integration (D-006 / GAP-07).
 */
interface AnalyzerIngestionPort
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload): void;
}
