<?php

namespace App\Services;

use App\Models\Result;
use App\Models\ResultAttachment;
use App\Models\TestOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    public const MAX_BYTES = 5_242_880; // 5 MB

    public function __construct(private readonly AuditLogger $audit) {}

    public function store(
        UploadedFile $file,
        User $actor,
        ?Result $result = null,
        ?TestOrder $order = null,
    ): ResultAttachment {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => 'يُسمح فقط بملفات PDF والصور (JPEG/PNG/GIF/WebP).',
            ]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'file' => 'حجم الملف يتجاوز الحد الأقصى (5 ميجابايت).',
            ]);
        }

        $patientId = $result?->patient_id ?? $order?->patient_id;
        if (! $patientId) {
            throw ValidationException::withMessages([
                'file' => 'يجب ربط المرفق بنتيجة أو طلب يحتوي على مريض.',
            ]);
        }

        $path = $file->store('attachments/'.now()->format('Y/m'), 'local');

        $attachment = ResultAttachment::query()->create([
            'result_id' => $result?->id,
            'test_order_id' => $order?->id ?? $result?->test_order_id,
            'patient_id' => $patientId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $actor->id,
            'uploaded_at' => now(),
        ]);

        $this->audit->log('attachments.uploaded', $attachment, null, [
            'original_name' => $attachment->original_name,
            'mime_type' => $attachment->mime_type,
            'size_bytes' => $attachment->size_bytes,
            'result_id' => $attachment->result_id,
            'test_order_id' => $attachment->test_order_id,
        ], $actor->id);

        return $attachment;
    }

    public function download(ResultAttachment $attachment, User $actor): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->audit->log('attachments.downloaded', $attachment, null, [
            'original_name' => $attachment->original_name,
        ], $actor->id);

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
        );
    }
}
