<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; direction: rtl; text-align: right; color: #14201a; font-size: 12px; }
        .header { border-bottom: 2px solid #0f6b4c; padding-bottom: 8px; margin-bottom: 16px; }
        .brand { font-size: 22px; font-weight: bold; color: #0f6b4c; }
        .meta { color: #4d6358; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #c9d8cf; padding: 8px; text-align: right; }
        th { background: #eef6f1; }
        .footer { margin-top: 24px; font-size: 10px; color: #4d6358; border-top: 1px solid #c9d8cf; padding-top: 8px; }
        .slot { margin-top: 18px; padding: 10px; border: 1px dashed #c9d8cf; color: #4d6358; }
    </style>
</head>
<body>
{{-- Presentation slots reserved for future logo / signature / QR / bilingual (D-016) --}}
<div class="header">
    <div class="brand">LabBI</div>
    <div class="meta">تقرير نتيجة مخبرية معتمدة</div>
    <div class="meta">تاريخ الإصدار: {{ now()->format('Y-m-d H:i') }}</div>
</div>

<table>
    <tr><th>المريض</th><td>{{ $result->patient?->full_name }}</td></tr>
    <tr><th>الرقم الطبي</th><td>{{ $result->patient?->medical_file_number }}</td></tr>
    <tr><th>الطبيب</th><td>{{ $result->order?->doctor?->user?->name }}</td></tr>
    <tr><th>رقم الطلب</th><td>{{ $result->order?->order_number }}</td></tr>
    <tr><th>الفحص</th><td>{{ $result->test?->name_ar }} ({{ $result->test?->code }})</td></tr>
    <tr><th>النتيجة</th><td>{{ $result->value_numeric }} {{ $result->unit }}</td></tr>
    <tr><th>التصنيف</th><td>{{ $result->classification }}</td></tr>
    <tr><th>حالة التقرير</th><td>معتمد</td></tr>
    <tr><th>تاريخ الاعتماد</th><td>{{ $result->approved_at?->format('Y-m-d H:i') }}</td></tr>
    <tr><th>المعتمد بواسطة</th><td>{{ $result->approvedBy?->name }}</td></tr>
</table>

@if($result->notes)
    <p><strong>ملاحظات:</strong> {{ $result->notes }}</p>
@endif

<div class="slot">مساحة الشعار / التوقيع / رمز QR — للقالب المعتمد لاحقاً</div>

<div class="footer">
    مستند سري — LabBI. هذا قالب عربي وظيفي (RTL) حتى اعتماد القالب النهائي.
</div>
</body>
</html>
