@include('errors.safe', [
    'code' => 403,
    'title' => 'غير مصرح',
    'message' => isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'ليس لديك صلاحية للوصول إلى هذه الصفحة.',
])
