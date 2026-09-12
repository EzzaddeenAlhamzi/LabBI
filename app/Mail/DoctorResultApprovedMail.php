<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\Result;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DoctorResultApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
        public Result $result,
        public string $secureUrl,
    ) {}

    public function envelope(): Envelope
    {
        $critical = $this->notification->priority === Notification::PRIORITY_CRITICAL;

        return new Envelope(
            subject: $critical
                ? '[LabBI] نتيجة حرجة معتمدة'
                : '[LabBI] نتيجة معتمدة جاهزة',
        );
    }

    public function content(): Content
    {
        $this->result->loadMissing(['patient', 'test', 'order']);

        return new Content(
            view: 'mail.doctor-result-approved',
            with: [
                'notification' => $this->notification,
                'result' => $this->result,
                'secureUrl' => $this->secureUrl,
            ],
        );
    }
}
