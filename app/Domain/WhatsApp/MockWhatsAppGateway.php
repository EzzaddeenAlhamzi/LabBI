<?php

namespace App\Domain\WhatsApp;

use App\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dev/mock WhatsApp provider — does NOT claim production delivery.
 * Failures can be forced via context['force_fail'] for tests.
 */
class MockWhatsAppGateway implements WhatsAppGateway
{
    public function send(string $toPhone, string $message, array $context = []): array
    {
        if (! empty($context['force_fail'])) {
            Log::warning('whatsapp.mock.failed', [
                'to' => $this->maskPhone($toPhone),
                'reason' => 'forced_fail',
            ]);

            return [
                'status' => 'failed',
                'provider_ref' => null,
                'error_summary' => 'Mock provider forced failure',
            ];
        }

        $ref = 'mock-wa-'.Str::uuid()->toString();

        Log::info('whatsapp.mock.sent', [
            'to' => $this->maskPhone($toPhone),
            'provider_ref' => $ref,
            'message_length' => mb_strlen($message),
        ]);

        return [
            'status' => 'sent',
            'provider_ref' => $ref,
            'error_summary' => null,
        ];
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
