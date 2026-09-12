<?php

namespace App\Services;

use App\Contracts\ResultApprovalContract;
use App\Models\User;

/**
 * Permission-based approval gate (REV-05 / D-008).
 * Initially only Admin role is seeded with results.approve.
 */
class ResultApprovalService implements ResultApprovalContract
{
    public const PERMISSION_APPROVE = 'results.approve';

    public const PERMISSION_RETURN = 'results.return';

    public function canApprove(int $userId): bool
    {
        $user = User::query()->with('roles.permissions')->find($userId);

        return $user?->hasPermission(self::PERMISSION_APPROVE) ?? false;
    }

    public function canReturn(int $userId): bool
    {
        $user = User::query()->with('roles.permissions')->find($userId);

        return $user?->hasPermission(self::PERMISSION_RETURN) ?? false;
    }
}
