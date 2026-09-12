<?php

namespace App\Jobs;

use App\Models\Result;
use App\Services\NotificationOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent post-approval orchestration entrypoint.
 */
class ProcessResultApprovedJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $resultId,
        public ?int $actorId = null,
    ) {}

    public function handle(NotificationOrchestrator $orchestrator): void
    {
        $result = Result::query()->find($this->resultId);
        if (! $result) {
            return;
        }

        if ($result->status !== Result::STATUS_APPROVED) {
            Log::info('process_result_approved.skipped_unapproved', ['result_id' => $this->resultId]);

            return;
        }

        $actor = $this->actorId ? \App\Models\User::query()->find($this->actorId) : null;
        $orchestrator->onResultApproved($result, $actor);
    }
}
