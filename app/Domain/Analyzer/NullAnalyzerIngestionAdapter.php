<?php

namespace App\Domain\Analyzer;

use App\Contracts\AnalyzerIngestionPort;
use RuntimeException;

/**
 * Stub adapter — Future HL7/ASTM/vendor integration.
 * Throws until a real adapter is registered in a later phase.
 */
class NullAnalyzerIngestionAdapter implements AnalyzerIngestionPort
{
    public function ingest(array $payload): void
    {
        throw new RuntimeException(
            'Analyzer ingestion is deferred to Future (D-006). V1 supports manual result entry only.'
        );
    }
}
