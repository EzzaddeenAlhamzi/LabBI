<?php

namespace Tests\Unit;

use App\Services\AuditLogger;
use Tests\TestCase;

class AuditLoggerSanitizeTest extends TestCase
{
    public function test_sanitize_redacts_nested_secrets(): void
    {
        $logger = app(AuditLogger::class);
        $clean = $logger->sanitize([
            'user' => [
                'password' => 'x',
                'name' => 'A',
            ],
            'token' => 'abc',
        ]);

        $this->assertSame('[redacted]', $clean['user']['password']);
        $this->assertSame('A', $clean['user']['name']);
        $this->assertSame('[redacted]', $clean['token']);
    }
}
