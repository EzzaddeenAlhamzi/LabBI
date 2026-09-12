<!DOCTYPE html>
<html lang="ar" dir="rtl">
<body style="font-family: Tahoma, sans-serif; direction: rtl; text-align: right;">
    <h2>LabBI — إشعار نتيجة معتمدة</h2>
    <p>{{ $notification->title }}</p>
    <p>{{ $notification->message }}</p>
    <ul>
        <li>المريض: {{ $result->patient?->full_name }}</li>
        <li>الفحص: {{ $result->test?->name_ar }}</li>
        <li>الحالة: معتمد</li>
        <li>الأولوية: {{ $notification->priority === 'critical' ? 'حرج' : 'عادي' }}</li>
    </ul>
    <p><a href="{{ $secureUrl }}">فتح التقرير الآمن / النتيجة</a></p>
</body>
</html>
