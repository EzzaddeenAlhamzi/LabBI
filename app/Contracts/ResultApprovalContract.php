<?php

namespace App\Contracts;

/**
 * Phase 1 contract only — full approval workflow UI/engine is Phase 2.
 * Authorization MUST use permission codes (e.g. results.approve), never role-name hardcoding.
 */
interface ResultApprovalContract
{
    public function canApprove(int $userId): bool;

    public function canReturn(int $userId): bool;
}
