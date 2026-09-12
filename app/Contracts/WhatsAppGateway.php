<?php

namespace App\Contracts;

/**
 * Provider-independent WhatsApp gateway (D-011).
 * Domain code must never import Twilio/Meta/UltraMsg SDKs.
 */
interface WhatsAppGateway
{
    /**
     * @return array{status: string, provider_ref: ?string, error_summary: ?string}
     *         status: sent|failed|queued
     */
    public function send(string $toPhone, string $message, array $context = []): array;
}
