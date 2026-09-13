<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\BiDashboardController;
use App\Http\Controllers\Admin\CriticalCasesController;
use App\Http\Controllers\Admin\DeliveryController as AdminDeliveryController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\LabStaffController;
use App\Http\Controllers\Admin\OperationalReportController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RangeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Doctor\ComparisonController as DoctorComparisonController;
use App\Http\Controllers\Doctor\DoctorDashboardController;
use App\Http\Controllers\Doctor\OrderController as DoctorOrderController;
use App\Http\Controllers\Doctor\PatientController as DoctorPatientController;
use App\Http\Controllers\Doctor\ReportController as DoctorReportController;
use App\Http\Controllers\Doctor\RequestController as DoctorRequestController;
use App\Http\Controllers\Doctor\ResultController as DoctorResultController;
use App\Http\Controllers\Lab\AttachmentController as LabAttachmentController;
use App\Http\Controllers\Lab\CompletedTestsController;
use App\Http\Controllers\Lab\CriticalAlertController as LabCriticalAlertController;
use App\Http\Controllers\Lab\LabDashboardController;
use App\Http\Controllers\Lab\OrderController as LabOrderController;
use App\Http\Controllers\Lab\PatientSearchController;
use App\Http\Controllers\Lab\ResultEntryController;
use App\Http\Controllers\Lab\ResultReviewController;
use App\Http\Controllers\Lab\SampleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patient\MyTestsController;
use App\Http\Controllers\Patient\PatientDashboardController;
use App\Http\Controllers\Patient\RequestController as PatientRequestController;
use App\Http\Controllers\ReportDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/health', HealthController::class)->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/profile', fn () => view('profile'))->name('profile');

    Route::middleware('permission:notifications.view_self')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read_all');
    });

    Route::get('/reports/{report}/download', ReportDownloadController::class)
        ->middleware('permission:pdf.download_own,pdf.download_clinical,pdf.generate')
        ->name('reports.download');

    Route::prefix('admin')->name('admin.')->middleware('permission:users.view,patients.view_all,catalog.manage,orders.view_all,bi.view')->group(function () {
        // Admin BI surfaces require explicit bi.view (or reports.operational where noted).
        // Do NOT OR with critical.view_all / patients.view_all — lab must not inherit Admin BI.
        Route::get('/', AdminDashboardController::class)
            ->middleware('permission:bi.view')
            ->name('dashboard');

        Route::get('/deliveries', [AdminDeliveryController::class, 'index'])
            ->middleware('permission:audit.view,reports.operational,bi.view')
            ->name('deliveries.index');

        Route::get('/bi', BiDashboardController::class)
            ->middleware('permission:bi.view')
            ->name('bi.index');

        Route::get('/reports/operational', [OperationalReportController::class, 'index'])
            ->middleware('permission:reports.operational,bi.view')
            ->name('reports.operational');

        Route::get('/critical-cases', [CriticalCasesController::class, 'index'])
            ->middleware('permission:bi.view')
            ->name('critical-cases.index');

        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
            Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->middleware('permission:users.activate')->name('users.toggle');
        });

        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        });

        Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.manage')->name('permissions.index');

        Route::middleware('permission:patients.view_all')->group(function () {
            Route::get('/patients', [AdminPatientController::class, 'index'])->name('patients.index');
            Route::get('/patients/create', [AdminPatientController::class, 'create'])->middleware('permission:patients.create')->name('patients.create');
            Route::post('/patients', [AdminPatientController::class, 'store'])->middleware('permission:patients.create')->name('patients.store');
            Route::get('/patients/{patient}', [AdminPatientController::class, 'show'])->name('patients.show');
            Route::get('/patients/{patient}/edit', [AdminPatientController::class, 'edit'])->middleware('permission:patients.update')->name('patients.edit');
            Route::put('/patients/{patient}', [AdminPatientController::class, 'update'])->middleware('permission:patients.update')->name('patients.update');
        });

        Route::middleware('permission:doctors.manage')->group(function () {
            Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
            Route::get('/doctors/create', [DoctorController::class, 'create'])->name('doctors.create');
            Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
        });

        Route::middleware('permission:lab_staff.manage')->group(function () {
            Route::get('/lab-staff', [LabStaffController::class, 'index'])->name('lab-staff.index');
            Route::get('/lab-staff/create', [LabStaffController::class, 'create'])->name('lab-staff.create');
            Route::post('/lab-staff', [LabStaffController::class, 'store'])->name('lab-staff.store');
        });

        Route::middleware('permission:departments.manage')->group(function () {
            Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        });

        Route::middleware('permission:catalog.view,catalog.manage')->group(function () {
            Route::get('/tests', [TestController::class, 'index'])->name('tests.index');
            Route::get('/tests/create', [TestController::class, 'create'])->middleware('permission:catalog.manage')->name('tests.create');
            Route::post('/tests', [TestController::class, 'store'])->middleware('permission:catalog.manage')->name('tests.store');
            Route::get('/tests/{test}', [TestController::class, 'show'])->name('tests.show');
        });

        Route::middleware('permission:ranges.manage')->group(function () {
            Route::get('/ranges', [RangeController::class, 'index'])->name('ranges.index');
            Route::post('/ranges', [RangeController::class, 'store'])->name('ranges.store');
        });

        Route::middleware('permission:orders.view_all')->group(function () {
            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/create', [AdminOrderController::class, 'create'])->middleware('permission:orders.create')->name('orders.create');
            Route::post('/orders', [AdminOrderController::class, 'store'])->middleware('permission:orders.create')->name('orders.store');
            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        });

        Route::middleware('permission:audit.view')->group(function () {
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
            Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit.show');
        });

        Route::middleware('permission:backups.manage')->group(function () {
            Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
            Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
            Route::get('/backups/{backup}', [BackupController::class, 'show'])->name('backups.show');
            Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
        });

        Route::middleware('permission:settings.manage')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/ack', [SettingsController::class, 'touch'])->name('settings.touch');
        });
    });

    Route::prefix('lab')->name('lab.')->middleware('permission:orders.view_lab,samples.receive')->group(function () {
        Route::get('/', LabDashboardController::class)->name('dashboard');
        Route::get('/orders', [LabOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [LabOrderController::class, 'show'])->name('orders.show');
        Route::get('/samples', [SampleController::class, 'index'])->name('samples.index');
        Route::get('/samples/reception', [SampleController::class, 'reception'])->middleware('permission:samples.receive')->name('samples.reception');
        Route::post('/samples/receive', [SampleController::class, 'receive'])->middleware('permission:samples.receive')->name('samples.receive');
        Route::post('/samples/{sample}/reject', [SampleController::class, 'reject'])->middleware('permission:samples.reject')->name('samples.reject');
        Route::get('/patients/search', PatientSearchController::class)->name('patients.search');

        Route::middleware('permission:results.enter')->group(function () {
            Route::get('/results/entry', [ResultEntryController::class, 'index'])->name('results.entry.index');
            Route::get('/results/entry/{order}', [ResultEntryController::class, 'edit'])->name('results.entry');
            Route::post('/results/entry/{order}/start-analysis', [ResultEntryController::class, 'startAnalysis'])
                ->middleware('permission:samples.start_analysis')
                ->name('results.start_analysis');
            Route::post('/results/items/{item}', [ResultEntryController::class, 'save'])->name('results.save');
        });

        Route::middleware('permission:results.view_all')->group(function () {
            Route::get('/results/review', [ResultReviewController::class, 'index'])->name('results.review.index');
            Route::get('/results/review/{result}', [ResultReviewController::class, 'show'])->name('results.review.show');
            Route::get('/completed', CompletedTestsController::class)->name('completed.index');
        });

        Route::post('/results/review/{result}/approve', [ResultReviewController::class, 'approve'])
            ->middleware('permission:results.approve')
            ->name('results.approve');
        Route::post('/results/review/{result}/return', [ResultReviewController::class, 'returnResult'])
            ->middleware('permission:results.return')
            ->name('results.return');
        Route::post('/results/review/{result}/reject', [ResultReviewController::class, 'reject'])
            ->middleware('permission:results.approve')
            ->name('results.reject');

        Route::middleware('permission:critical.view_all')->group(function () {
            Route::get('/critical', [LabCriticalAlertController::class, 'index'])->name('critical.index');
            Route::get('/critical/{alert}', [LabCriticalAlertController::class, 'show'])->name('critical.show');
            Route::post('/critical/{alert}/acknowledge', [LabCriticalAlertController::class, 'acknowledge'])->name('critical.acknowledge');
        });

        Route::middleware('permission:attachments.view')->group(function () {
            Route::get('/attachments', [LabAttachmentController::class, 'index'])->name('attachments.index');
            Route::get('/attachments/{attachment}/download', [LabAttachmentController::class, 'download'])->name('attachments.download');
        });
        Route::post('/attachments', [LabAttachmentController::class, 'store'])
            ->middleware('permission:attachments.upload')
            ->name('attachments.store');
    });

    Route::prefix('doctor')->name('doctor.')->middleware('permission:orders.view_own_doctor,orders.create')->group(function () {
        Route::get('/', DoctorDashboardController::class)->name('dashboard');
        Route::get('/patients', [DoctorPatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [DoctorPatientController::class, 'show'])->name('patients.show');
        Route::get('/orders', [DoctorOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/create', [DoctorOrderController::class, 'create'])->middleware('permission:orders.create')->name('orders.create');
        Route::post('/orders', [DoctorOrderController::class, 'store'])->middleware('permission:orders.create')->name('orders.store');
        Route::get('/orders/{order}', [DoctorOrderController::class, 'show'])->name('orders.show');

        Route::middleware('permission:results.view_own_doctor')->group(function () {
            Route::get('/results', [DoctorResultController::class, 'index'])->name('results.index');
            Route::get('/results/{result}', [DoctorResultController::class, 'show'])->name('results.show');
        });
        Route::get('/critical', [DoctorResultController::class, 'critical'])
            ->middleware('permission:critical.view_own')
            ->name('critical.index');
        Route::get('/reports', [DoctorReportController::class, 'index'])
            ->middleware('permission:pdf.download_clinical')
            ->name('reports.index');
        Route::get('/comparison', [DoctorComparisonController::class, 'index'])
            ->middleware('permission:comparison.use')
            ->name('comparison.index');
        Route::middleware('permission:requests.manage_doctor')->group(function () {
            Route::get('/requests', [DoctorRequestController::class, 'index'])->name('requests.index');
            Route::post('/requests/{patientRequest}/under-review', [DoctorRequestController::class, 'underReview'])->name('requests.under_review');
            Route::post('/requests/{patientRequest}/resolve', [DoctorRequestController::class, 'resolve'])->name('requests.resolve');
            Route::post('/requests/{patientRequest}/reject', [DoctorRequestController::class, 'reject'])->name('requests.reject');
        });
    });

    Route::prefix('patient')->name('patient.')->middleware('permission:patients.view_self')->group(function () {
        Route::get('/', [PatientDashboardController::class, '__invoke'])->name('dashboard');
        Route::get('/profile', [PatientDashboardController::class, 'profile'])->name('profile');
        Route::get('/records/{patient}', [PatientDashboardController::class, 'show'])->name('records.show');
        Route::middleware('permission:results.view_self,orders.view_self')->group(function () {
            Route::get('/tests', [MyTestsController::class, 'index'])->name('tests.index');
            Route::get('/results/{result}', [MyTestsController::class, 'show'])->name('results.show');
        });
        Route::middleware('permission:requests.view_own')->group(function () {
            Route::get('/requests', [PatientRequestController::class, 'index'])->name('requests.index');
            Route::post('/requests', [PatientRequestController::class, 'store'])
                ->middleware('permission:requests.create')
                ->name('requests.store');
        });
    });
});
