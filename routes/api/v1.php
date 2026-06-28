<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes — Production-aligned, stateless JSON API
|--------------------------------------------------------------------------
|
| All endpoints are prefixed with /api/v1 via routes/api.php
| Middleware: auth:sanctum + school.isolated + school.licensed (protected)
|
| Canonical paths (avoid duplicating these elsewhere):
|   Auth user context  → GET  /auth/me
|   School settings    → GET  /settings/school  (not legacy /settings)
|   Academic calendar  → GET  /enterprise/academic/calendar
|   Executive overview → GET  /enterprise/command-center  (not /executive/dashboard)
|   Student resources  → /students/{student}/*  (nested under one param)
|
*/

// ─── Public (rate-limited per Fortify starter-kit) ─────────────────────────
Route::post('/auth/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/auth/forgot-password', [App\Http\Controllers\Api\V1\AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-reset');
Route::post('/auth/reset-password', [App\Http\Controllers\Api\V1\AuthController::class, 'resetPassword'])
    ->middleware('throttle:password-reset');
Route::post('/schools/register', [App\Http\Controllers\Api\V1\SchoolController::class, 'register'])
    ->middleware('throttle:registration');

Route::get('/platform/certificates/verify/{code}', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'verifyCertificate']);
Route::post('/integrations/token', [App\Http\Controllers\Platform\ExternalIntegrationController::class, 'token'])
    ->middleware('throttle:login');

// ─── Authenticated ────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'school.isolated', 'school.licensed'])->group(function () {

    // License (accessible even when expired — middleware excludes these paths)
    Route::get('/license/status', [App\Http\Controllers\Api\V1\LicenseController::class, 'status']);
    Route::post('/license/activate', [App\Http\Controllers\Api\V1\LicenseController::class, 'activate']);

    // Vendor license management (super admin only)
    Route::middleware('super_admin')->group(function () {
        Route::get('/admin/schools', [App\Http\Controllers\Api\V1\AdminSchoolController::class, 'index']);
        Route::prefix('admin/licenses')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\V1\AdminLicenseController::class, 'index']);
            Route::post('/', [App\Http\Controllers\Api\V1\AdminLicenseController::class, 'store']);
            Route::post('/{id}/revoke', [App\Http\Controllers\Api\V1\AdminLicenseController::class, 'revoke']);
        });
    });

    // Auth & profile
    Route::post('/auth/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
    Route::get('/auth/me', [App\Http\Controllers\Api\V1\AuthController::class, 'me']);
    Route::get('/user/profile', [App\Http\Controllers\Api\V1\ProfileController::class, 'show']);
    Route::put('/user/profile', [App\Http\Controllers\Api\V1\ProfileController::class, 'update']);
    Route::post('/user/change-password', [App\Http\Controllers\Api\V1\AuthController::class, 'changePassword']);

    // School
    Route::get('/school', [App\Http\Controllers\Api\V1\SchoolController::class, 'show']);
    Route::put('/school', [App\Http\Controllers\Api\V1\SchoolController::class, 'update']);

    // School settings, terminology & custom fields (SaaS customization)
    Route::get('/settings/school', [App\Http\Controllers\Api\V1\SettingsController::class, 'schoolSettings']);
    Route::put('/settings/school', [App\Http\Controllers\Api\V1\SettingsController::class, 'updateSchoolSettings']);
    Route::get('/settings/config', [App\Http\Controllers\Api\V1\SettingsController::class, 'publicConfig']);
    Route::get('/settings/terminology', [App\Http\Controllers\Api\V1\SettingsController::class, 'terminology']);
    Route::put('/settings/terminology', [App\Http\Controllers\Api\V1\SettingsController::class, 'updateTerminology']);
    Route::get('/settings/custom-fields', [App\Http\Controllers\Api\V1\SettingsController::class, 'customFields']);
    Route::post('/settings/custom-fields', [App\Http\Controllers\Api\V1\SettingsController::class, 'storeCustomField']);
    Route::delete('/settings/custom-fields/{customField}', [App\Http\Controllers\Api\V1\SettingsController::class, 'destroyCustomField']);

    // AI school assistant
    Route::post('/assistant/chat', [App\Http\Controllers\Api\V1\AssistantController::class, 'chat'])
        ->middleware('throttle:assistant');
    Route::get('/assistant/conversations', [App\Http\Controllers\Api\V1\AssistantController::class, 'conversations']);
    Route::get('/assistant/conversations/{conversationId}', [App\Http\Controllers\Api\V1\AssistantController::class, 'showConversation']);

    // Dashboard (operational)
    Route::get('/dashboard/kpis', [App\Http\Controllers\DashboardController::class, 'kpis']);
    Route::get('/dashboard/activity', [App\Http\Controllers\DashboardController::class, 'activity']);
    Route::get('/dashboard/monthly-stats', [App\Http\Controllers\DashboardController::class, 'monthlyStats']);
    Route::get('/dashboard/recent-activity', [App\Http\Controllers\DashboardController::class, 'recentActivity']);

    // Users & roles
    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::get('/users/{id}', [App\Http\Controllers\UserController::class, 'show']);
    Route::put('/users/{id}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{id}', [App\Http\Controllers\UserController::class, 'destroy']);
    Route::patch('/users/{id}/activate', [App\Http\Controllers\UserController::class, 'activate']);
    Route::patch('/users/{id}/deactivate', [App\Http\Controllers\UserController::class, 'deactivate']);
    Route::post('/users/{id}/reset-password', [App\Http\Controllers\UserController::class, 'resetPassword']);
    Route::patch('/users/{id}/role', [App\Http\Controllers\UserController::class, 'assignRole']);
    Route::get('/roles', [App\Http\Controllers\RoleController::class, 'index']);
    Route::get('/roles/{id}', [App\Http\Controllers\RoleController::class, 'show']);
    Route::post('/roles', [App\Http\Controllers\RoleController::class, 'store']);
    Route::put('/roles/{id}', [App\Http\Controllers\RoleController::class, 'update']);
    Route::delete('/roles/{id}', [App\Http\Controllers\RoleController::class, 'destroy']);
    Route::get('/permissions', [App\Http\Controllers\PermissionController::class, 'index']);

    // Students — CRUD (V1) + nested resources (single canonical tree)
    Route::get('/students', [App\Http\Controllers\Api\V1\StudentController::class, 'index']);
    Route::post('/students', [App\Http\Controllers\Api\V1\StudentController::class, 'store']);
    Route::post('/students/promote', [App\Http\Controllers\StudentController::class, 'promote']);
    Route::post('/students/bulk/invoices', [App\Http\Controllers\StudentController::class, 'bulkInvoices']);
    Route::post('/students/bulk/status', [App\Http\Controllers\StudentController::class, 'bulkStatus']);
    Route::post('/students/bulk/promote', [App\Http\Controllers\StudentController::class, 'bulkPromote']);
    Route::get('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'show']);
    Route::put('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'update']);
    Route::delete('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'destroy']);
    Route::get('/students/{student}/performance', [App\Http\Controllers\StudentController::class, 'performance']);
    Route::get('/students/{student}/lifecycle', [App\Http\Controllers\StudentLifecycleController::class, 'show']);
    Route::get('/students/{student}/invoices', [App\Http\Controllers\StudentController::class, 'invoices']);
    Route::post('/students/{student}/invoices', [App\Http\Controllers\StudentController::class, 'createInvoice']);
    Route::post('/students/{student}/documents', [App\Http\Controllers\StudentController::class, 'uploadDocuments']);
    Route::get('/students/{student}/exams', [App\Http\Controllers\StudentController::class, 'exams']);
    Route::get('/students/{student}/guardians', [App\Http\Controllers\GuardianController::class, 'forStudent']);

    // Inventory / uniform store
    Route::get('/inventory/items', [App\Http\Controllers\InventoryController::class, 'index']);
    Route::post('/inventory/items', [App\Http\Controllers\InventoryController::class, 'store']);
    Route::put('/inventory/items/{id}', [App\Http\Controllers\InventoryController::class, 'update']);
    Route::post('/inventory/items/{id}/restock', [App\Http\Controllers\InventoryController::class, 'restock']);
    Route::get('/inventory/sales', [App\Http\Controllers\InventoryController::class, 'sales']);
    Route::post('/inventory/sales', [App\Http\Controllers\InventoryController::class, 'createSale']);

    // Holiday lessons (separate from regular term)
    Route::get('/holiday-programs', [App\Http\Controllers\HolidayProgramController::class, 'index']);
    Route::post('/holiday-programs', [App\Http\Controllers\HolidayProgramController::class, 'store']);
    Route::put('/holiday-programs/{id}', [App\Http\Controllers\HolidayProgramController::class, 'update']);
    Route::get('/holiday-programs/{id}/enrollments', [App\Http\Controllers\HolidayProgramController::class, 'enrollments']);
    Route::post('/holiday-programs/{id}/enroll', [App\Http\Controllers\HolidayProgramController::class, 'enroll']);
    Route::get('/holiday-programs/{id}/attendance', [App\Http\Controllers\HolidayProgramController::class, 'attendance']);
    Route::post('/holiday-programs/{id}/attendance', [App\Http\Controllers\HolidayProgramController::class, 'recordAttendance']);

    // Teachers (V1)
    Route::get('/teachers', [App\Http\Controllers\Api\V1\TeacherController::class, 'index']);
    Route::post('/teachers', [App\Http\Controllers\Api\V1\TeacherController::class, 'store']);
    Route::get('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'show']);
    Route::put('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'update']);
    Route::delete('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'destroy']);
    Route::patch('/teachers/{id}/status', [App\Http\Controllers\TeacherController::class, 'updateStatus']);

    // Academic
    Route::get('/classes', [App\Http\Controllers\ClassController::class, 'index']);
    Route::get('/classes/{id}', [App\Http\Controllers\ClassController::class, 'show']);
    Route::post('/classes', [App\Http\Controllers\ClassController::class, 'store']);
    Route::put('/classes/{id}', [App\Http\Controllers\ClassController::class, 'update']);
    Route::delete('/classes/{id}', [App\Http\Controllers\ClassController::class, 'destroy']);
    Route::get('/subjects', [App\Http\Controllers\SubjectController::class, 'index']);
    Route::get('/subjects/{id}', [App\Http\Controllers\SubjectController::class, 'show']);
    Route::post('/subjects', [App\Http\Controllers\SubjectController::class, 'store']);
    Route::put('/subjects/{id}', [App\Http\Controllers\SubjectController::class, 'update']);
    Route::delete('/subjects/{id}', [App\Http\Controllers\SubjectController::class, 'destroy']);
    Route::get('/departments', [App\Http\Controllers\DepartmentController::class, 'index']);
    Route::get('/departments/{id}', [App\Http\Controllers\DepartmentController::class, 'show']);
    Route::post('/departments', [App\Http\Controllers\DepartmentController::class, 'store']);
    Route::put('/departments/{id}', [App\Http\Controllers\DepartmentController::class, 'update']);
    Route::delete('/departments/{id}', [App\Http\Controllers\DepartmentController::class, 'destroy']);
    Route::get('/grade-levels', [App\Http\Controllers\GradeLevelController::class, 'index']);
    Route::get('/grade-levels/{id}', [App\Http\Controllers\GradeLevelController::class, 'show']);
    Route::post('/grade-levels', [App\Http\Controllers\GradeLevelController::class, 'store']);
    Route::put('/grade-levels/{id}', [App\Http\Controllers\GradeLevelController::class, 'update']);
    Route::delete('/grade-levels/{id}', [App\Http\Controllers\GradeLevelController::class, 'destroy']);
    Route::get('/grading-scales', [App\Http\Controllers\GradingScaleController::class, 'index']);
    Route::post('/grading-scales', [App\Http\Controllers\GradingScaleController::class, 'store']);
    Route::put('/grading-scales/{id}', [App\Http\Controllers\GradingScaleController::class, 'update']);
    Route::delete('/grading-scales/{id}', [App\Http\Controllers\GradingScaleController::class, 'destroy']);
    Route::post('/grading-scales/get-grade', [App\Http\Controllers\GradingScaleController::class, 'getGradeForScore']);
    Route::get('/rooms', [App\Http\Controllers\RoomController::class, 'index']);
    Route::post('/rooms', [App\Http\Controllers\RoomController::class, 'store']);
    Route::put('/rooms/{id}', [App\Http\Controllers\RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [App\Http\Controllers\RoomController::class, 'destroy']);
    Route::get('/terms', [App\Http\Controllers\TermController::class, 'index']);
    Route::get('/terms/current', [App\Http\Controllers\TermController::class, 'current']);
    Route::get('/terms/{id}', [App\Http\Controllers\TermController::class, 'show']);
    Route::post('/terms', [App\Http\Controllers\TermController::class, 'store']);
    Route::put('/terms/{id}', [App\Http\Controllers\TermController::class, 'update']);
    Route::delete('/terms/{id}', [App\Http\Controllers\TermController::class, 'destroy']);
    Route::get('/assignments', [App\Http\Controllers\AssignmentController::class, 'index']);
    Route::post('/assignments', [App\Http\Controllers\AssignmentController::class, 'store']);
    Route::get('/assignments/{id}', [App\Http\Controllers\AssignmentController::class, 'show']);
    Route::put('/assignments/{id}', [App\Http\Controllers\AssignmentController::class, 'update']);
    Route::delete('/assignments/{id}', [App\Http\Controllers\AssignmentController::class, 'destroy']);
    Route::get('/grades/class/{classId}', [App\Http\Controllers\GradeController::class, 'getByClass']);
    Route::get('/grades/student/{studentId}', [App\Http\Controllers\GradeController::class, 'getByStudent']);
    Route::post('/grades', [App\Http\Controllers\GradeController::class, 'store']);
    Route::post('/grades/bulk', [App\Http\Controllers\GradeController::class, 'bulkUpload']);
    Route::get('/grades/class/{classId}/performance', [App\Http\Controllers\GradeController::class, 'performance']);
    Route::get('/exams', [App\Http\Controllers\ExamController::class, 'index']);
    Route::get('/exams/analytics/performance', [App\Http\Controllers\ExamController::class, 'analytics']);
    Route::get('/exams/{id}', [App\Http\Controllers\ExamController::class, 'show']);
    Route::post('/exams', [App\Http\Controllers\ExamController::class, 'store']);
    Route::put('/exams/{id}', [App\Http\Controllers\ExamController::class, 'update']);
    Route::delete('/exams/{id}', [App\Http\Controllers\ExamController::class, 'destroy']);
    Route::post('/exams/{id}/results/approve', [App\Http\Controllers\ExamController::class, 'approveResults']);
    Route::post('/exams/{id}/publish', [App\Http\Controllers\ExamController::class, 'publish']);
    Route::post('/exams/{id}/results', [App\Http\Controllers\ExamController::class, 'recordResults']);
    Route::get('/tests', [App\Http\Controllers\TestController::class, 'index']);
    Route::get('/tests/{id}', [App\Http\Controllers\TestController::class, 'show']);
    Route::post('/tests', [App\Http\Controllers\TestController::class, 'store']);
    Route::put('/tests/{id}', [App\Http\Controllers\TestController::class, 'update']);
    Route::delete('/tests/{id}', [App\Http\Controllers\TestController::class, 'destroy']);
    Route::post('/tests/{id}/results', [App\Http\Controllers\TestController::class, 'recordResults']);
    Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index']);
    Route::post('/attendance', [App\Http\Controllers\AttendanceController::class, 'store']);
    Route::get('/attendance/today/summary', [App\Http\Controllers\AttendanceController::class, 'todaySummary']);
    Route::get('/attendance/student/{id}/summary', [App\Http\Controllers\AttendanceController::class, 'studentSummary']);
    Route::get('/attendance/class/{id}/report', [App\Http\Controllers\AttendanceController::class, 'classReport']);
    Route::get('/timetable', [App\Http\Controllers\TimetableController::class, 'index']);
    Route::post('/timetable', [App\Http\Controllers\TimetableController::class, 'store']);
    Route::put('/timetable/{id}', [App\Http\Controllers\TimetableController::class, 'update']);
    Route::delete('/timetable/{id}', [App\Http\Controllers\TimetableController::class, 'destroy']);
    Route::post('/timetable/generate', [App\Http\Controllers\TimetableController::class, 'generate']);
    Route::post('/timetable/generate-bulk', [App\Http\Controllers\TimetableController::class, 'generateBulk']);
    Route::get('/teacher-assignments', [App\Http\Controllers\TeacherAssignmentController::class, 'index']);
    Route::post('/teacher-assignments', [App\Http\Controllers\TeacherAssignmentController::class, 'store']);
    Route::put('/teacher-assignments/{id}', [App\Http\Controllers\TeacherAssignmentController::class, 'update']);
    Route::delete('/teacher-assignments/{id}', [App\Http\Controllers\TeacherAssignmentController::class, 'destroy']);

    // Finance
    Route::get('/payments', [App\Http\Controllers\PaymentController::class, 'index']);
    Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store']);
    Route::get('/payments/{id}/receipt', [App\Http\Controllers\PaymentController::class, 'receipt']);
    Route::post('/payments/{id}/reverse', [App\Http\Controllers\PaymentController::class, 'reverse']);
    Route::delete('/payments/{id}', [App\Http\Controllers\PaymentController::class, 'destroy']);
    Route::get('/transactions', [App\Http\Controllers\TransactionController::class, 'index']);
    Route::get('/transactions/summary', [App\Http\Controllers\TransactionController::class, 'summary']);
    Route::get('/invoices', [App\Http\Controllers\InvoiceController::class, 'index']);
    Route::post('/invoices', [App\Http\Controllers\InvoiceController::class, 'store']);
    Route::get('/invoices/{id}', [App\Http\Controllers\InvoiceController::class, 'show']);
    Route::put('/invoices/{id}', [App\Http\Controllers\InvoiceController::class, 'update']);
    Route::get('/fee-structures', [App\Http\Controllers\FeeStructureController::class, 'index']);
    Route::post('/fee-structures', [App\Http\Controllers\FeeStructureController::class, 'store']);
    Route::put('/fee-structures/{id}', [App\Http\Controllers\FeeStructureController::class, 'update']);
    Route::delete('/fee-structures/{id}', [App\Http\Controllers\FeeStructureController::class, 'destroy']);
    Route::get('/fee-categories', [App\Http\Controllers\FeeCategoryController::class, 'index']);
    Route::get('/fee-categories/{id}', [App\Http\Controllers\FeeCategoryController::class, 'show']);
    Route::post('/fee-categories', [App\Http\Controllers\FeeCategoryController::class, 'store']);
    Route::put('/fee-categories/{id}', [App\Http\Controllers\FeeCategoryController::class, 'update']);
    Route::delete('/fee-categories/{id}', [App\Http\Controllers\FeeCategoryController::class, 'destroy']);
    Route::get('/payroll', [App\Http\Controllers\PayrollController::class, 'index']);
    Route::get('/payroll/teachers', [App\Http\Controllers\PayrollController::class, 'getTeachers']);
    Route::get('/payroll/summary', [App\Http\Controllers\PayrollController::class, 'summary']);
    Route::get('/payroll/trends', [App\Http\Controllers\PayrollController::class, 'trends']);
    Route::get('/payroll/department-summary', [App\Http\Controllers\PayrollController::class, 'departmentSummary']);
    Route::get('/payroll/teachers/{id}/history', [App\Http\Controllers\PayrollController::class, 'teacherHistory']);
    Route::get('/payroll/{id}/payslip', [App\Http\Controllers\PayrollController::class, 'payslip']);
    Route::post('/payroll/generate', [App\Http\Controllers\PayrollController::class, 'generate']);
    Route::put('/payroll/{id}', [App\Http\Controllers\PayrollController::class, 'update']);
    Route::post('/payroll/{id}/process', [App\Http\Controllers\PayrollController::class, 'process']);
    Route::get('/finance/summary', [App\Http\Controllers\FinanceController::class, 'summary']);
    Route::get('/finance/outstanding-balances', [App\Http\Controllers\FinanceController::class, 'outstandingBalances']);
    Route::get('/finance/aging', [App\Http\Controllers\FinanceController::class, 'aging']);
    Route::get('/finance/reconciliation', [App\Http\Controllers\FinanceController::class, 'reconciliation']);
    Route::get('/finance/reports/{period}', [App\Http\Controllers\FinanceController::class, 'periodReport']);

    // People & enrollment
    Route::get('/parents/{id}/children', [App\Http\Controllers\ParentController::class, 'children']);

    // Parent / guardian portal
    Route::prefix('parent/portal')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ParentPortalController::class, 'dashboard']);
        Route::get('/children', [App\Http\Controllers\ParentPortalController::class, 'children']);
        Route::get('/students/{studentId}/results', [App\Http\Controllers\ParentPortalController::class, 'results']);
        Route::get('/students/{studentId}/attendance', [App\Http\Controllers\ParentPortalController::class, 'attendance']);
        Route::get('/students/{studentId}/fees', [App\Http\Controllers\ParentPortalController::class, 'fees']);
        Route::get('/students/{studentId}/discipline', [App\Http\Controllers\ParentPortalController::class, 'discipline']);
        Route::get('/students/{studentId}/progress', [App\Http\Controllers\ParentPortalController::class, 'progress']);
        Route::get('/announcements', [App\Http\Controllers\ParentPortalController::class, 'announcements']);
        Route::get('/notifications', [App\Http\Controllers\ParentPortalController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [App\Http\Controllers\ParentPortalController::class, 'markNotificationRead']);
        Route::post('/notifications/read-all', [App\Http\Controllers\ParentPortalController::class, 'markAllNotificationsRead']);
        Route::get('/communications/threads', [App\Http\Controllers\ParentPortalController::class, 'threads']);
        Route::post('/communications/threads', [App\Http\Controllers\ParentPortalController::class, 'createThread']);
        Route::get('/communications/threads/{threadId}/messages', [App\Http\Controllers\ParentPortalController::class, 'threadMessages']);
        Route::post('/communications/threads/{threadId}/messages', [App\Http\Controllers\ParentPortalController::class, 'sendMessage']);
    });

    Route::get('/disciplinary-records', [App\Http\Controllers\DisciplinaryRecordController::class, 'index']);
    Route::post('/disciplinary-records', [App\Http\Controllers\DisciplinaryRecordController::class, 'store']);
    Route::get('/disciplinary-records/{id}', [App\Http\Controllers\DisciplinaryRecordController::class, 'show']);

    Route::get('/communications/threads', [App\Http\Controllers\CommunicationController::class, 'index']);
    Route::get('/communications/threads/{id}', [App\Http\Controllers\CommunicationController::class, 'show']);
    Route::post('/communications/threads/{id}/messages', [App\Http\Controllers\CommunicationController::class, 'reply']);

    Route::get('/guardians', [App\Http\Controllers\GuardianController::class, 'index']);
    Route::get('/guardians/{id}', [App\Http\Controllers\GuardianController::class, 'show']);
    Route::get('/guardians/{id}/students', [App\Http\Controllers\GuardianController::class, 'students']);
    Route::post('/guardians', [App\Http\Controllers\GuardianController::class, 'store']);
    Route::put('/guardians/{id}', [App\Http\Controllers\GuardianController::class, 'update']);
    Route::post('/guardians/{id}/link-student', [App\Http\Controllers\GuardianController::class, 'linkToStudent']);
    Route::get('/enrollment-applications', [App\Http\Controllers\EnrollmentController::class, 'index']);
    Route::post('/enrollment-applications', [App\Http\Controllers\EnrollmentController::class, 'store']);
    Route::get('/enrollment-applications/{id}', [App\Http\Controllers\EnrollmentController::class, 'show']);
    Route::put('/enrollment-applications/{id}/approve', [App\Http\Controllers\EnrollmentController::class, 'approve']);
    Route::put('/enrollment-applications/{id}/reject', [App\Http\Controllers\EnrollmentController::class, 'reject']);
    Route::get('/leave-requests', [App\Http\Controllers\LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [App\Http\Controllers\LeaveRequestController::class, 'store']);
    Route::post('/leave-requests/{id}/approve', [App\Http\Controllers\LeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{id}/reject', [App\Http\Controllers\LeaveRequestController::class, 'reject']);

    // Reports & announcements
    Route::get('/reports/export', [App\Http\Controllers\ReportController::class, 'export']);
    Route::get('/reports/academic-performance', [App\Http\Controllers\ReportController::class, 'academicPerformance']);
    Route::get('/reports/attendance', [App\Http\Controllers\ReportController::class, 'attendance']);
    Route::get('/reports/financial', [App\Http\Controllers\ReportController::class, 'financial']);
    Route::get('/reports/class/{id}', [App\Http\Controllers\ReportController::class, 'classReport']);
    Route::get('/reports/{reportType}', [App\Http\Controllers\ReportController::class, 'generate']);
    Route::post('/reports', [App\Http\Controllers\ReportController::class, 'store']);
    Route::get('/announcements', [App\Http\Controllers\AnnouncementController::class, 'index']);
    Route::get('/announcements/{id}', [App\Http\Controllers\AnnouncementController::class, 'show']);
    Route::post('/announcements', [App\Http\Controllers\AnnouncementController::class, 'store']);
    Route::put('/announcements/{id}', [App\Http\Controllers\AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [App\Http\Controllers\AnnouncementController::class, 'destroy']);

    // School ERP modules
    Route::get('/analytics/insights', [App\Http\Controllers\AnalyticsController::class, 'index']);
    Route::get('/workflows/pending', [App\Http\Controllers\WorkflowController::class, 'pending']);
    Route::get('/workflows/history', [App\Http\Controllers\WorkflowController::class, 'history']);
    Route::get('/workflows/{id}', [App\Http\Controllers\WorkflowController::class, 'show']);
    Route::post('/workflows/{id}/approve', [App\Http\Controllers\WorkflowController::class, 'approve']);
    Route::post('/workflows/{id}/reject', [App\Http\Controllers\WorkflowController::class, 'reject']);
    Route::get('/procurement/requisitions', [App\Http\Controllers\ProcurementController::class, 'requisitions']);
    Route::post('/procurement/requisitions', [App\Http\Controllers\ProcurementController::class, 'storeRequisition']);
    Route::get('/procurement/vendors', [App\Http\Controllers\ProcurementController::class, 'vendors']);
    Route::post('/procurement/vendors', [App\Http\Controllers\ProcurementController::class, 'storeVendor']);
    Route::post('/procurement/goods-receipts', [App\Http\Controllers\ProcurementController::class, 'receiveGoods']);
    Route::get('/assets', [App\Http\Controllers\AssetController::class, 'index']);
    Route::post('/assets', [App\Http\Controllers\AssetController::class, 'store']);
    Route::post('/assets/{id}/maintenance', [App\Http\Controllers\AssetController::class, 'logMaintenance']);
    Route::post('/assets/{id}/dispose', [App\Http\Controllers\AssetController::class, 'dispose']);
    Route::get('/transport/vehicles', [App\Http\Controllers\TransportController::class, 'vehicles']);
    Route::post('/transport/vehicles', [App\Http\Controllers\TransportController::class, 'storeVehicle']);
    Route::get('/transport/drivers', [App\Http\Controllers\TransportController::class, 'drivers']);
    Route::post('/transport/drivers', [App\Http\Controllers\TransportController::class, 'storeDriver']);
    Route::get('/transport/routes', [App\Http\Controllers\TransportController::class, 'routes']);
    Route::post('/transport/routes', [App\Http\Controllers\TransportController::class, 'storeRoute']);
    Route::post('/transport/allocations', [App\Http\Controllers\TransportController::class, 'allocateStudent']);
    Route::get('/hostels', [App\Http\Controllers\HostelController::class, 'index']);
    Route::post('/hostels', [App\Http\Controllers\HostelController::class, 'store']);
    Route::post('/hostels/{id}/rooms', [App\Http\Controllers\HostelController::class, 'storeRoom']);
    Route::post('/hostels/allocations', [App\Http\Controllers\HostelController::class, 'allocate']);
    Route::get('/library/books', [App\Http\Controllers\LibraryController::class, 'books']);
    Route::post('/library/books', [App\Http\Controllers\LibraryController::class, 'storeBook']);
    Route::post('/library/loans', [App\Http\Controllers\LibraryController::class, 'borrow']);
    Route::post('/library/loans/{id}/return', [App\Http\Controllers\LibraryController::class, 'returnBook']);
    Route::get('/visitors', [App\Http\Controllers\VisitorController::class, 'index']);
    Route::post('/visitors/check-in', [App\Http\Controllers\VisitorController::class, 'checkIn']);
    Route::post('/visitors/{id}/check-out', [App\Http\Controllers\VisitorController::class, 'checkOut']);
    Route::get('/health/students/{id}/profile', [App\Http\Controllers\HealthController::class, 'profile']);
    Route::put('/health/students/{id}/profile', [App\Http\Controllers\HealthController::class, 'updateProfile']);
    Route::get('/health/clinic-visits', [App\Http\Controllers\HealthController::class, 'visits']);
    Route::post('/health/clinic-visits', [App\Http\Controllers\HealthController::class, 'recordVisit']);
    Route::get('/events', [App\Http\Controllers\SchoolEventController::class, 'index']);
    Route::post('/events', [App\Http\Controllers\SchoolEventController::class, 'store']);
    Route::get('/compliance/policies', [App\Http\Controllers\ComplianceController::class, 'policies']);
    Route::post('/compliance/policies', [App\Http\Controllers\ComplianceController::class, 'storePolicy']);
    Route::get('/compliance/incidents', [App\Http\Controllers\ComplianceController::class, 'incidents']);
    Route::post('/compliance/incidents', [App\Http\Controllers\ComplianceController::class, 'storeIncident']);
    Route::get('/consent-forms', [App\Http\Controllers\ConsentFormController::class, 'index']);
    Route::post('/consent-forms', [App\Http\Controllers\ConsentFormController::class, 'store']);
    Route::get('/parent/portal/consent-forms', [App\Http\Controllers\ConsentFormController::class, 'parentForms']);
    Route::post('/parent/portal/consent-forms/{id}/respond', [App\Http\Controllers\ConsentFormController::class, 'respond']);

    // Audit trail
    Route::get('/audit-logs/login-history', [App\Http\Controllers\AuditLogController::class, 'loginHistory']);
    Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index']);
    Route::get('/audit-logs/{id}', [App\Http\Controllers\AuditLogController::class, 'show']);

    // Advanced platform features
    Route::prefix('platform')->group(function () {
        Route::get('/policy-rules', [App\Http\Controllers\Platform\PlatformCoreController::class, 'policyRules']);
        Route::post('/policy-rules', [App\Http\Controllers\Platform\PlatformCoreController::class, 'storePolicyRule']);
        Route::post('/policy-rules/evaluate', [App\Http\Controllers\Platform\PlatformCoreController::class, 'evaluatePolicy']);
        Route::get('/workflow-definitions', [App\Http\Controllers\Platform\PlatformCoreController::class, 'workflowDefinitions']);
        Route::post('/workflow-definitions', [App\Http\Controllers\Platform\PlatformCoreController::class, 'saveWorkflowDefinition']);
        Route::get('/branches/tree', [App\Http\Controllers\Platform\PlatformCoreController::class, 'branchTree']);
        Route::post('/branches', [App\Http\Controllers\Platform\PlatformCoreController::class, 'createBranch']);
        Route::get('/retention-policies', [App\Http\Controllers\Platform\PlatformCoreController::class, 'retentionPolicies']);
        Route::post('/retention-policies', [App\Http\Controllers\Platform\PlatformCoreController::class, 'storeRetentionPolicy']);
        Route::get('/masking-rules', [App\Http\Controllers\Platform\PlatformCoreController::class, 'maskingRules']);
        Route::post('/masking-rules', [App\Http\Controllers\Platform\PlatformCoreController::class, 'storeMaskingRule']);
        Route::get('/students/{id}/versions', [App\Http\Controllers\Platform\PlatformCoreController::class, 'studentVersions']);

        Route::post('/communications/send', [App\Http\Controllers\Platform\PlatformCommunicationController::class, 'send']);
        Route::get('/communications/tracking', [App\Http\Controllers\Platform\PlatformCommunicationController::class, 'tracking']);
        Route::post('/communications/deliveries/{id}/read', [App\Http\Controllers\Platform\PlatformCommunicationController::class, 'markRead']);

        Route::get('/documents', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'documents']);
        Route::post('/documents', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'createDocument']);
        Route::post('/documents/{id}/sign', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'signDocument']);
        Route::get('/certificates', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'certificates']);
        Route::post('/certificates/issue', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'issueCertificate']);
        Route::get('/vault', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'vaultIndex']);
        Route::post('/vault', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'vaultStore']);
        Route::get('/vault/{id}', [App\Http\Controllers\Platform\PlatformDocumentController::class, 'vaultRetrieve']);

        Route::get('/scholarships', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'scholarships']);
        Route::post('/scholarships', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'storeScholarship']);
        Route::post('/scholarships/{id}/apply', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'applyScholarship']);
        Route::post('/scholarship-applications/{id}/review', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'reviewScholarshipApplication']);
        Route::get('/payment-gateways', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'gatewayConfigs']);
        Route::post('/payment-gateways', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'storeGatewayConfig']);
        Route::post('/payments/initiate', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'initiatePayment']);
        Route::get('/fee-penalty-rules', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'penaltyRules']);
        Route::post('/fee-penalty-rules', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'storePenaltyRule']);
        Route::get('/refunds', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'refunds']);
        Route::post('/refunds', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'requestRefund']);
        Route::post('/refunds/{id}/approve', [App\Http\Controllers\Platform\PlatformFinanceController::class, 'approveRefund']);

        Route::get('/behavior-points', [App\Http\Controllers\Platform\PlatformStaffController::class, 'behaviorPoints']);
        Route::post('/behavior-points', [App\Http\Controllers\Platform\PlatformStaffController::class, 'storeBehaviorPoint']);
        Route::get('/behavior-points/students/{studentId}/summary', [App\Http\Controllers\Platform\PlatformStaffController::class, 'behaviorSummary']);
        Route::get('/interventions', [App\Http\Controllers\Platform\PlatformStaffController::class, 'interventions']);
        Route::post('/interventions', [App\Http\Controllers\Platform\PlatformStaffController::class, 'storeIntervention']);
        Route::get('/staff-tasks', [App\Http\Controllers\Platform\PlatformStaffController::class, 'tasks']);
        Route::post('/staff-tasks', [App\Http\Controllers\Platform\PlatformStaffController::class, 'storeTask']);
        Route::put('/staff-tasks/{id}', [App\Http\Controllers\Platform\PlatformStaffController::class, 'updateTask']);
        Route::get('/staff-feed', [App\Http\Controllers\Platform\PlatformStaffController::class, 'feed']);
        Route::post('/staff-feed', [App\Http\Controllers\Platform\PlatformStaffController::class, 'postFeed']);
        Route::post('/staff-feed/{id}/comments', [App\Http\Controllers\Platform\PlatformStaffController::class, 'commentFeed']);

        Route::get('/operations/live', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'liveDashboard']);
        Route::post('/operations/alerts/{id}/resolve', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'resolveAlert']);
        Route::get('/analytics/predictive', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'predictiveAnalytics']);
        Route::get('/system/health', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'systemHealth']);
        Route::get('/audit-integrity/verify', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'verifyAuditIntegrity']);
        Route::get('/api-clients', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'apiClients']);
        Route::post('/api-clients', [App\Http\Controllers\Platform\PlatformOperationsController::class, 'createApiClient']);
    });

    // Enterprise ERP modules (16-module vision)
    Route::prefix('enterprise')->group(function () {
        Route::get('/command-center', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'commandCenter']);

        Route::get('/academic/curriculum', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'curriculum']);
        Route::post('/academic/curriculum', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'publishCurriculum']);
        Route::post('/academic/learning-outcomes', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'recordOutcome']);
        Route::get('/academic/assessment-categories', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'assessmentCategories']);
        Route::post('/academic/assessment-categories', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'storeAssessmentCategory']);
        Route::post('/academic/continuous-assessments', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'recordAssessment']);
        Route::get('/academic/students/{studentId}/subjects/{subjectId}/weighted-grade', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'weightedGrade']);
        Route::get('/academic/gpa-ranking', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'gpaRanking']);
        Route::post('/academic/gradebook-rules', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'storeGradebookRule']);
        Route::get('/academic/students/{studentId}/subjects/{subjectId}/prerequisites', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'checkPrerequisites']);
        Route::get('/academic/promotion-rules', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'promotionRules']);
        Route::post('/academic/promotion-rules', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'storePromotionRule']);
        Route::get('/academic/students/{studentId}/promotion-evaluation', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'evaluatePromotion']);
        Route::get('/academic/calendar', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'calendar']);
        Route::post('/academic/calendar', [App\Http\Controllers\Enterprise\EnterpriseAcademicController::class, 'storeCalendarEntry']);

        Route::post('/finance/accounts/seed', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'seedAccounts']);
        Route::get('/finance/accounts', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'accounts']);
        Route::post('/finance/journals', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'postJournal']);
        Route::get('/finance/exchange-rates', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'exchangeRates']);
        Route::post('/finance/exchange-rates', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'storeExchangeRate']);
        Route::get('/finance/instalment-plans', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'instalmentPlans']);
        Route::post('/finance/instalment-plans', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'createInstalmentPlan']);
        Route::get('/finance/bank-statements', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'bankStatements']);
        Route::post('/finance/bank-statements', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'importBankLine']);
        Route::post('/finance/bank-statements/{lineId}/reconcile', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'reconcile']);
        Route::get('/finance/reports/profit-loss', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'profitAndLoss']);
        Route::get('/finance/reports/balance-sheet', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'balanceSheet']);
        Route::get('/finance/cashflow-forecast', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'cashflowForecast']);
        Route::get('/finance/revenue-rules', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'revenueRules']);
        Route::post('/finance/revenue-rules', [App\Http\Controllers\Enterprise\EnterpriseFinanceController::class, 'storeRevenueRule']);

        Route::get('/exams/question-bank', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'questions']);
        Route::post('/exams/question-bank', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'storeQuestion']);
        Route::post('/exams/generate-paper', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'generatePaper']);
        Route::post('/exams/cbt/start', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'startCbt']);
        Route::post('/exams/cbt/{id}/submit', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'submitCbt']);
        Route::post('/exams/cbt/{sessionId}/anti-cheat', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'antiCheatLog']);
        Route::get('/exams/remark-requests', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'remarkRequests']);
        Route::post('/exams/remark-requests', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'requestRemark']);
        Route::get('/workflows/delegations', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'delegations']);
        Route::post('/workflows/delegations', [App\Http\Controllers\Enterprise\EnterpriseExamController::class, 'storeDelegation']);

        Route::post('/admissions/score', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'admissionScore']);
        Route::get('/students/{id}/profile', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'studentProfile']);
        Route::get('/students/{id}/timeline', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'studentTimeline']);
        Route::post('/students/{id}/timeline', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'recordTimelineEvent']);
        Route::get('/early-warnings', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'earlyWarnings']);
        Route::get('/alumni', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'alumni']);
        Route::post('/alumni/students/{studentId}', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'registerAlumni']);
        Route::get('/campaigns', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'campaigns']);
        Route::post('/campaigns', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'storeCampaign']);
        Route::post('/campaigns/{id}/send', [App\Http\Controllers\Enterprise\EnterpriseIntelligenceController::class, 'sendCampaign']);

        Route::get('/hr/performance-reviews', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'performanceReviews']);
        Route::post('/hr/performance-reviews', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storePerformanceReview']);
        Route::get('/hr/contracts', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'contracts']);
        Route::post('/hr/contracts', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeContract']);
        Route::get('/hr/certifications', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'certifications']);
        Route::post('/hr/certifications', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeCertification']);
        Route::get('/compliance/requirements', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'complianceRequirements']);
        Route::post('/compliance/requirements', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeComplianceRequirement']);
        Route::get('/integrations/connectors', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'connectors']);
        Route::post('/integrations/connectors', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeConnector']);
        Route::post('/integrations/webhooks', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeWebhook']);
        Route::get('/security/abac-policies', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'abacPolicies']);
        Route::post('/security/abac-policies', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeAbacPolicy']);
        Route::get('/group/policies', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'groupPolicies']);
        Route::post('/group/policies', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'storeGroupPolicy']);
        Route::get('/group/transfers', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'crossSchoolTransfers']);
        Route::post('/group/transfers', [App\Http\Controllers\Enterprise\EnterpriseGovernanceController::class, 'requestTransfer']);
    });
});
