<?php

use App\Http\Controllers\AcademicStructureController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Api\V1\AdminLicenseController;
use App\Http\Controllers\Api\V1\AdminSchoolController;
use App\Http\Controllers\Api\V1\AssistantController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TwoFactorAuthController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SchoolController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\StudentPortalController;
use App\Http\Controllers\Api\V1\TeacherPortalController;
use App\Http\Controllers\Api\V1\PeopleImportController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CertificateTemplateController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\ConsentFormController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\DisciplinaryRecordController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\Enterprise\EnterpriseAcademicController;
use App\Http\Controllers\Enterprise\EnterpriseExamController;
use App\Http\Controllers\Enterprise\EnterpriseFinanceController;
use App\Http\Controllers\Enterprise\EnterpriseGovernanceController;
use App\Http\Controllers\Enterprise\EnterpriseIntelligenceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\ExpenseHeadController;
use App\Http\Controllers\FeeCategoryController;
use App\Http\Controllers\FeeDiscountController;
use App\Http\Controllers\FeeGroupController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GradingScaleController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HelpDeskController;
use App\Http\Controllers\HolidayProgramController;
use App\Http\Controllers\HostelController;
use App\Http\Controllers\IncomeHeadController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LibraryMemberController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\Platform\ExternalIntegrationController;
use App\Http\Controllers\Platform\PlatformCommunicationController;
use App\Http\Controllers\Platform\PlatformCoreController;
use App\Http\Controllers\Platform\PlatformDocumentController;
use App\Http\Controllers\Platform\PlatformFinanceController;
use App\Http\Controllers\Platform\PlatformOperationsController;
use App\Http\Controllers\Platform\PlatformStaffController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SchoolCertificateController;
use App\Http\Controllers\SchoolCurrencyController;
use App\Http\Controllers\SchoolEventController;
use App\Http\Controllers\SchoolLanguageController;
use App\Http\Controllers\SchoolTripController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Controllers\StudentCategoryController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentLifecycleController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\WorkflowController;
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
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');
Route::post('/auth/two-factor/challenge', [TwoFactorAuthController::class, 'challenge'])
    ->middleware('throttle:login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-reset');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:password-reset');
Route::post('/schools/register', [SchoolController::class, 'register'])
    ->middleware('throttle:registration');

Route::get('/platform/certificates/verify/{code}', [PlatformDocumentController::class, 'verifyCertificate']);
Route::get('/certificates/verify/{code}', [SchoolCertificateController::class, 'verify']);
Route::get('/auth/platform-terms', [AuthController::class, 'platformTerms']);
Route::get('/auth/privacy-policy', [AuthController::class, 'privacyPolicy']);
Route::get('/settings/config', [SettingsController::class, 'publicConfig']);
Route::post('/integrations/token', [ExternalIntegrationController::class, 'token'])
    ->middleware('throttle:login');
Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');
Route::get('/webhooks/payments/{provider}/sandbox-checkout', [PaymentWebhookController::class, 'sandboxCheckout'])
    ->middleware('throttle:30,1');

// ─── Authenticated ────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'school.isolated', 'school.licensed', '2fa.enabled'])->group(function () {

    // License (accessible even when expired — middleware excludes these paths)
    Route::get('/license/status', [LicenseController::class, 'status']);
    Route::post('/license/activate', [LicenseController::class, 'activate']);
    Route::post('/auth/accept-platform-terms', [AuthController::class, 'acceptPlatformTerms']);

    // Vendor license management (super admin only)
    Route::middleware('super_admin')->group(function () {
        Route::get('/admin/schools', [AdminSchoolController::class, 'index']);
        Route::post('/admin/schools', [AdminSchoolController::class, 'store']);
        Route::get('/admin/schools/{school}', [AdminSchoolController::class, 'show']);
        Route::patch('/admin/schools/{school}/status', [AdminSchoolController::class, 'updateStatus']);
        Route::delete('/admin/schools/{school}', [AdminSchoolController::class, 'destroy']);
        Route::get('/admin/schools/{school}/usage', [AdminSchoolController::class, 'usage']);
        Route::get('/admin/schools/{school}/backups', [AdminSchoolController::class, 'listBackups']);
        Route::post('/admin/schools/{school}/backups', [AdminSchoolController::class, 'createBackup']);
        Route::post('/admin/schools/{school}/backups/{backup}/restore', [AdminSchoolController::class, 'restoreBackup']);
        Route::get('/admin/schools/{school}/domains', [AdminSchoolController::class, 'domains']);
        Route::post('/admin/schools/{school}/domains', [AdminSchoolController::class, 'storeDomain']);
        Route::patch('/admin/schools/{school}/domains/{domain}', [AdminSchoolController::class, 'updateDomain']);
        Route::get('/admin/schools/{school}/domains/{domain}/verification', [AdminSchoolController::class, 'checkDomainVerification']);
        Route::post('/admin/schools/{school}/domains/{domain}/verify', [AdminSchoolController::class, 'verifyDomain']);
        Route::delete('/admin/schools/{school}/domains/{domain}', [AdminSchoolController::class, 'destroyDomain']);
        Route::prefix('admin/licenses')->group(function () {
            Route::get('/', [AdminLicenseController::class, 'index']);
            Route::post('/', [AdminLicenseController::class, 'store']);
            Route::post('/{id}/revoke', [AdminLicenseController::class, 'revoke']);
        });
    });

    // Auth & profile
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/schools', [AuthController::class, 'schools']);
    Route::post('/auth/switch-school', [AuthController::class, 'switchSchool']);
    Route::get('/auth/two-factor', [TwoFactorAuthController::class, 'status']);
    Route::post('/auth/two-factor', [TwoFactorAuthController::class, 'enable']);
    Route::post('/auth/two-factor/confirm', [TwoFactorAuthController::class, 'confirm']);
    Route::delete('/auth/two-factor', [TwoFactorAuthController::class, 'disable']);
    Route::post('/auth/two-factor/recovery-codes', [TwoFactorAuthController::class, 'recoveryCodes']);
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);

    // Generic staff file upload (resources, homework, avatars, report cards).
    Route::post('/uploads', [UploadController::class, 'store'])
        ->middleware('capability:isStaff');

    // Teacher portal (scoped to the authenticated teacher).
    Route::prefix('teacher-portal')->group(function () {
        Route::get('/dashboard', [TeacherPortalController::class, 'dashboard']);
        Route::get('/classes', [TeacherPortalController::class, 'myClasses']);
        Route::get('/students/{studentId}', [TeacherPortalController::class, 'classStudentDetail']);

        Route::post('/attendance/submit', [TeacherPortalController::class, 'submitAttendance']);
        Route::post('/attendance/lock', [TeacherPortalController::class, 'lockAttendance']);
        Route::get('/attendance/reports', [TeacherPortalController::class, 'attendanceReports']);

        Route::get('/timetable/free-periods', [TeacherPortalController::class, 'freePeriods']);
        Route::get('/timetable/exams', [TeacherPortalController::class, 'examTimetable']);
        Route::get('/timetable/change-requests', [TeacherPortalController::class, 'timetableChangeRequests']);
        Route::post('/timetable/change-requests', [TeacherPortalController::class, 'storeTimetableChangeRequest']);

        Route::get('/calendar', [TeacherPortalController::class, 'calendar']);

        Route::get('/lesson-plans', [TeacherPortalController::class, 'lessonPlans']);
        Route::post('/lesson-plans', [TeacherPortalController::class, 'storeLessonPlan']);
        Route::put('/lesson-plans/{id}', [TeacherPortalController::class, 'updateLessonPlan']);

        Route::get('/syllabus-topics', [TeacherPortalController::class, 'syllabusTopics']);
        Route::post('/syllabus-topics', [TeacherPortalController::class, 'storeSyllabusTopic']);
        Route::put('/syllabus-topics/{id}', [TeacherPortalController::class, 'updateSyllabusTopic']);

        Route::get('/resources', [TeacherPortalController::class, 'resources']);
        Route::post('/resources', [TeacherPortalController::class, 'storeResource']);

        Route::get('/assignments/{assignmentId}/submissions', [TeacherPortalController::class, 'assignmentSubmissions']);
        Route::post('/submissions', [TeacherPortalController::class, 'storeSubmission']);
        Route::post('/submissions/{id}/grade', [TeacherPortalController::class, 'gradeSubmission']);

        Route::get('/online-lessons', [TeacherPortalController::class, 'onlineLessons']);
        Route::post('/online-lessons', [TeacherPortalController::class, 'storeOnlineLesson']);

        Route::get('/report-cards', [TeacherPortalController::class, 'reportCards']);
        Route::post('/report-cards', [TeacherPortalController::class, 'storeReportCard']);

        Route::get('/behaviour', [TeacherPortalController::class, 'behaviourPoints']);
        Route::post('/behaviour', [TeacherPortalController::class, 'storeBehaviourPoint']);
        Route::get('/interventions', [TeacherPortalController::class, 'interventions']);
        Route::post('/interventions', [TeacherPortalController::class, 'storeIntervention']);
        Route::get('/participation', [TeacherPortalController::class, 'participation']);
        Route::post('/participation', [TeacherPortalController::class, 'storeParticipation']);

        Route::get('/department', [TeacherPortalController::class, 'department']);
        Route::get('/leave', [TeacherPortalController::class, 'myLeave']);
        Route::post('/leave', [TeacherPortalController::class, 'applyLeave']);
        Route::get('/substitutions', [TeacherPortalController::class, 'substitutions']);
        Route::post('/substitutions/{id}/accept', [TeacherPortalController::class, 'acceptSubstitution']);

        Route::get('/notifications', [TeacherPortalController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [TeacherPortalController::class, 'markNotificationRead']);
        Route::post('/notifications/read-all', [TeacherPortalController::class, 'markAllNotificationsRead']);

        Route::post('/ai/generate', [TeacherPortalController::class, 'aiGenerate']);
        Route::get('/export', [TeacherPortalController::class, 'export']);
    });

    // School
    Route::get('/school', [SchoolController::class, 'show']);
    Route::put('/school', [SchoolController::class, 'update']);

    // School settings, terminology & custom fields (SaaS customization)
    Route::get('/settings/school', [SettingsController::class, 'schoolSettings']);
    Route::put('/settings/school', [SettingsController::class, 'updateSchoolSettings']);
    Route::get('/settings/terminology', [SettingsController::class, 'terminology']);
    Route::put('/settings/terminology', [SettingsController::class, 'updateTerminology']);
    Route::get('/settings/custom-fields', [SettingsController::class, 'customFields']);
    Route::post('/settings/custom-fields', [SettingsController::class, 'storeCustomField']);
    Route::put('/settings/custom-fields/{customField}', [SettingsController::class, 'updateCustomField']);
    Route::delete('/settings/custom-fields/{customField}', [SettingsController::class, 'destroyCustomField']);

    // AI school assistant
    Route::get('/assistant/status', [AssistantController::class, 'status']);
    Route::post('/assistant/chat', [AssistantController::class, 'chat'])
        ->middleware('throttle:assistant');
    Route::get('/assistant/conversations', [AssistantController::class, 'conversations']);
    Route::get('/assistant/conversations/{conversationId}', [AssistantController::class, 'showConversation']);

    // Dashboard (operational)
    Route::get('/dashboard/kpis', [DashboardController::class, 'kpis']);
    Route::get('/dashboard/school-widgets', [DashboardController::class, 'schoolWidgets']);
    Route::get('/dashboard/lms-widgets', [DashboardController::class, 'lmsWidgets']);
    Route::get('/dashboard/role-preview/{role}', [DashboardController::class, 'rolePreview']);
    Route::get('/dashboard/activity', [DashboardController::class, 'activity']);
    Route::get('/dashboard/monthly-stats', [DashboardController::class, 'monthlyStats']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'recentActivity']);

    // Users & roles
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::patch('/users/{id}/activate', [UserController::class, 'activate']);
    Route::patch('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
    Route::patch('/users/{id}/role', [UserController::class, 'assignRole']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);
    Route::get('/permissions', [PermissionController::class, 'index']);

    // Students — CRUD (V1) + nested resources (single canonical tree)
    Route::get('/students', [App\Http\Controllers\Api\V1\StudentController::class, 'index']);
    Route::post('/students', [App\Http\Controllers\Api\V1\StudentController::class, 'store']);
    Route::post('/students/promote', [StudentController::class, 'promote']);
    Route::post('/students/bulk/invoices', [StudentController::class, 'bulkInvoices']);
    Route::post('/students/bulk/status', [StudentController::class, 'bulkStatus']);
    Route::post('/students/bulk/promote', [StudentController::class, 'bulkPromote']);
    Route::get('/imports/{type}/template', [PeopleImportController::class, 'template'])
        ->whereIn('type', ['students', 'teachers', 'employees', 'guardians']);
    Route::post('/imports/{type}', [PeopleImportController::class, 'import'])
        ->whereIn('type', ['students', 'teachers', 'employees', 'guardians']);
    Route::get('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'show']);
    Route::put('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'update']);
    Route::delete('/students/{student}', [App\Http\Controllers\Api\V1\StudentController::class, 'destroy']);
    Route::get('/students/{student}/performance', [StudentController::class, 'performance']);
    Route::get('/students/{student}/lifecycle', [StudentLifecycleController::class, 'show']);
    Route::post('/students/{student}/placements', [StudentLifecycleController::class, 'place']);
    Route::post('/students/{student}/lifecycle/transition', [StudentLifecycleController::class, 'transition']);
    Route::post('/students/{student}/lifecycle/promote', [StudentLifecycleController::class, 'promoteOne']);
    Route::get('/students/{student}/transfer-certificate', [StudentLifecycleController::class, 'transferCertificate']);
    Route::get('/students/{student}/transcript', [StudentLifecycleController::class, 'transcript']);
    Route::get('/students/{student}/invoices', [StudentController::class, 'invoices']);
    Route::post('/students/{student}/invoices', [StudentController::class, 'createInvoice']);
    Route::get('/students/{student}/documents', [StudentController::class, 'documents']);
    Route::post('/students/{student}/documents', [StudentController::class, 'uploadDocuments']);
    Route::delete('/students/{student}/documents/{document}', [StudentController::class, 'destroyDocument']);
    Route::get('/students/{student}/exams', [StudentController::class, 'exams']);
    Route::get('/students/{student}/results/download', [StudentController::class, 'downloadResults']);
    Route::get('/students/{student}/id-card/print', [StudentController::class, 'printIdCard']);
    Route::post('/students/{student}/photo', [StudentController::class, 'uploadPhoto']);
    Route::get('/students/{student}/guardians', [GuardianController::class, 'forStudent']);

    Route::get('/streams', [AcademicStructureController::class, 'streams']);
    Route::post('/streams', [AcademicStructureController::class, 'storeStream']);
    Route::put('/streams/{id}', [AcademicStructureController::class, 'updateStream']);
    Route::get('/houses', [AcademicStructureController::class, 'houses']);
    Route::post('/houses', [AcademicStructureController::class, 'storeHouse']);
    Route::put('/houses/{id}', [AcademicStructureController::class, 'updateHouse']);
    Route::get('/subject-packages', [AcademicStructureController::class, 'subjectPackages']);
    Route::post('/subject-packages', [AcademicStructureController::class, 'storeSubjectPackage']);
    Route::get('/subject-packages/{id}', [AcademicStructureController::class, 'showSubjectPackage']);
    Route::put('/subject-packages/{id}', [AcademicStructureController::class, 'updateSubjectPackage']);
    Route::delete('/subject-packages/{id}', [AcademicStructureController::class, 'destroySubjectPackage']);

    // Inventory / uniform store
    Route::middleware('capability:canManageInventory')->group(function () {
        Route::get('/inventory/items', [InventoryController::class, 'index']);
        Route::post('/inventory/items', [InventoryController::class, 'store']);
        Route::put('/inventory/items/{id}', [InventoryController::class, 'update']);
        Route::post('/inventory/items/{id}/restock', [InventoryController::class, 'restock']);
        Route::get('/inventory/sales', [InventoryController::class, 'sales']);
        Route::post('/inventory/sales', [InventoryController::class, 'createSale']);
    });

    // Holiday lessons (separate from regular term)
    Route::get('/holiday-programs', [HolidayProgramController::class, 'index']);
    Route::post('/holiday-programs', [HolidayProgramController::class, 'store']);
    Route::put('/holiday-programs/{id}', [HolidayProgramController::class, 'update']);
    Route::get('/holiday-programs/{id}/enrollments', [HolidayProgramController::class, 'enrollments']);
    Route::post('/holiday-programs/{id}/enroll', [HolidayProgramController::class, 'enroll']);
    Route::get('/holiday-programs/{id}/attendance', [HolidayProgramController::class, 'attendance']);
    Route::post('/holiday-programs/{id}/attendance', [HolidayProgramController::class, 'recordAttendance']);

    Route::get('/school-trips', [SchoolTripController::class, 'index']);
    Route::post('/school-trips', [SchoolTripController::class, 'store']);
    Route::put('/school-trips/{id}', [SchoolTripController::class, 'update']);
    Route::get('/school-trips/{id}/enrollments', [SchoolTripController::class, 'enrollments']);
    Route::post('/school-trips/{id}/enroll', [SchoolTripController::class, 'enroll']);

    // Teachers (V1)
    Route::get('/teachers', [App\Http\Controllers\Api\V1\TeacherController::class, 'index']);
    Route::post('/teachers', [App\Http\Controllers\Api\V1\TeacherController::class, 'store']);
    Route::get('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'show']);
    Route::put('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'update']);
    Route::delete('/teachers/{teacher}', [App\Http\Controllers\Api\V1\TeacherController::class, 'destroy']);
    Route::patch('/teachers/{id}/status', [TeacherController::class, 'updateStatus']);

    // Academic
    Route::get('/classes', [ClassController::class, 'index']);
    Route::get('/classes/{class}', [ClassController::class, 'show']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::put('/classes/{class}', [ClassController::class, 'update']);
    Route::delete('/classes/{class}', [ClassController::class, 'destroy']);
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
    Route::post('/departments', [DepartmentController::class, 'store']);
    Route::put('/departments/{department}', [DepartmentController::class, 'update']);
    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::get('/grade-levels', [GradeLevelController::class, 'index']);
    Route::get('/grade-levels/{id}', [GradeLevelController::class, 'show']);
    Route::post('/grade-levels', [GradeLevelController::class, 'store']);
    Route::put('/grade-levels/{id}', [GradeLevelController::class, 'update']);
    Route::delete('/grade-levels/{id}', [GradeLevelController::class, 'destroy']);
    Route::get('/grading-scales', [GradingScaleController::class, 'index']);
    Route::post('/grading-scales', [GradingScaleController::class, 'store']);
    Route::put('/grading-scales/{id}', [GradingScaleController::class, 'update']);
    Route::delete('/grading-scales/{id}', [GradingScaleController::class, 'destroy']);
    Route::post('/grading-scales/get-grade', [GradingScaleController::class, 'getGradeForScore']);
    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::put('/rooms/{id}', [RoomController::class, 'update']);
    Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
    Route::get('/terms', [TermController::class, 'index']);
    Route::get('/terms/current', [TermController::class, 'current']);
    Route::get('/terms/{id}', [TermController::class, 'show']);
    Route::post('/terms', [TermController::class, 'store']);
    Route::put('/terms/{id}', [TermController::class, 'update']);
    Route::delete('/terms/{id}', [TermController::class, 'destroy']);
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::post('/assignments', [AssignmentController::class, 'store']);
    Route::get('/assignments/{id}', [AssignmentController::class, 'show']);
    Route::put('/assignments/{id}', [AssignmentController::class, 'update']);
    Route::delete('/assignments/{id}', [AssignmentController::class, 'destroy']);
    Route::get('/grades/class/{classId}', [GradeController::class, 'getByClass']);
    Route::get('/grades/student/{studentId}', [GradeController::class, 'getByStudent']);
    Route::post('/grades', [GradeController::class, 'store']);
    Route::post('/grades/bulk', [GradeController::class, 'bulkUpload']);
    Route::get('/grades/class/{classId}/performance', [GradeController::class, 'performance']);
    Route::get('/exams', [ExamController::class, 'index']);
    Route::get('/exams/analytics/performance', [ExamController::class, 'analytics']);
    Route::get('/exams/{id}', [ExamController::class, 'show']);
    Route::post('/exams', [ExamController::class, 'store']);
    Route::put('/exams/{id}', [ExamController::class, 'update']);
    Route::delete('/exams/{id}', [ExamController::class, 'destroy']);
    Route::post('/exams/{id}/results/approve', [ExamController::class, 'approveResults']);
    Route::post('/exams/{id}/publish', [ExamController::class, 'publish']);
    Route::post('/exams/{id}/results', [ExamController::class, 'recordResults']);
    Route::get('/exam-schedules', [ExamScheduleController::class, 'index']);
    Route::get('/exam-schedules/{id}', [ExamScheduleController::class, 'show']);
    Route::post('/exam-schedules', [ExamScheduleController::class, 'store']);
    Route::put('/exam-schedules/{id}', [ExamScheduleController::class, 'update']);
    Route::delete('/exam-schedules/{id}', [ExamScheduleController::class, 'destroy']);
    Route::get('/tests', [TestController::class, 'index']);
    Route::get('/tests/{test}', [TestController::class, 'show']);
    Route::post('/tests', [TestController::class, 'store']);
    Route::put('/tests/{test}', [TestController::class, 'update']);
    Route::delete('/tests/{test}', [TestController::class, 'destroy']);
    Route::post('/tests/{test}/results', [TestController::class, 'recordResults']);
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/today/summary', [AttendanceController::class, 'todaySummary']);
    Route::get('/attendance/student/{id}/summary', [AttendanceController::class, 'studentSummary']);
    Route::get('/attendance/class/{id}/report', [AttendanceController::class, 'classReport']);
    Route::get('/timetable', [TimetableController::class, 'index']);
    Route::post('/timetable', [TimetableController::class, 'store']);
    Route::put('/timetable/{id}', [TimetableController::class, 'update']);
    Route::delete('/timetable/{id}', [TimetableController::class, 'destroy']);
    Route::post('/timetable/generate', [TimetableController::class, 'generate']);
    Route::post('/timetable/generate-bulk', [TimetableController::class, 'generateBulk']);
    Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index']);
    Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store']);
    Route::put('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'update']);
    Route::delete('/teacher-assignments/{id}', [TeacherAssignmentController::class, 'destroy']);

    // Finance
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);
    Route::post('/payments/{payment}/reverse', [PaymentController::class, 'reverse']);
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update']);
    Route::get('/fee-structures', [FeeStructureController::class, 'index']);
    Route::post('/fee-structures', [FeeStructureController::class, 'store']);
    Route::put('/fee-structures/{id}', [FeeStructureController::class, 'update']);
    Route::delete('/fee-structures/{id}', [FeeStructureController::class, 'destroy']);
    Route::get('/fee-categories', [FeeCategoryController::class, 'index']);
    Route::get('/fee-categories/{id}', [FeeCategoryController::class, 'show']);
    Route::post('/fee-categories', [FeeCategoryController::class, 'store']);
    Route::put('/fee-categories/{id}', [FeeCategoryController::class, 'update']);
    Route::delete('/fee-categories/{id}', [FeeCategoryController::class, 'destroy']);
    Route::get('/fee-groups', [FeeGroupController::class, 'index']);
    Route::get('/fee-groups/{id}', [FeeGroupController::class, 'show']);
    Route::post('/fee-groups', [FeeGroupController::class, 'store']);
    Route::put('/fee-groups/{id}', [FeeGroupController::class, 'update']);
    Route::delete('/fee-groups/{id}', [FeeGroupController::class, 'destroy']);
    Route::get('/fee-discounts', [FeeDiscountController::class, 'index']);
    Route::get('/fee-discounts/{id}', [FeeDiscountController::class, 'show']);
    Route::post('/fee-discounts', [FeeDiscountController::class, 'store']);
    Route::put('/fee-discounts/{id}', [FeeDiscountController::class, 'update']);
    Route::delete('/fee-discounts/{id}', [FeeDiscountController::class, 'destroy']);
    Route::get('/income-heads', [IncomeHeadController::class, 'index']);
    Route::get('/income-heads/{id}', [IncomeHeadController::class, 'show']);
    Route::post('/income-heads', [IncomeHeadController::class, 'store']);
    Route::put('/income-heads/{id}', [IncomeHeadController::class, 'update']);
    Route::delete('/income-heads/{id}', [IncomeHeadController::class, 'destroy']);
    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::get('/budgets/{id}', [BudgetController::class, 'show']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::put('/budgets/{id}', [BudgetController::class, 'update']);
    Route::delete('/budgets/{id}', [BudgetController::class, 'destroy']);
    Route::post('/budgets/{id}/submit', [BudgetController::class, 'submit']);
    Route::post('/budgets/{id}/approve', [BudgetController::class, 'approve']);
    Route::post('/budgets/{id}/spend', [BudgetController::class, 'recordSpend']);
    Route::get('/expense-heads', [ExpenseHeadController::class, 'index']);
    Route::get('/expense-heads/{id}', [ExpenseHeadController::class, 'show']);
    Route::post('/expense-heads', [ExpenseHeadController::class, 'store']);
    Route::put('/expense-heads/{id}', [ExpenseHeadController::class, 'update']);
    Route::delete('/expense-heads/{id}', [ExpenseHeadController::class, 'destroy']);
    Route::get('/payroll', [PayrollController::class, 'index']);
    Route::get('/payroll/teachers', [PayrollController::class, 'getTeachers']);
    Route::get('/payroll/summary', [PayrollController::class, 'summary']);
    Route::get('/payroll/trends', [PayrollController::class, 'trends']);
    Route::get('/payroll/department-summary', [PayrollController::class, 'departmentSummary']);
    Route::get('/payroll/teachers/{id}/history', [PayrollController::class, 'teacherHistory']);
    Route::get('/payroll/{id}/payslip', [PayrollController::class, 'payslip']);
    Route::post('/payroll/generate', [PayrollController::class, 'generate']);
    Route::put('/payroll/{id}', [PayrollController::class, 'update']);
    Route::post('/payroll/{id}/process', [PayrollController::class, 'process']);
    Route::middleware('capability:canManageFinance')->group(function () {
        Route::get('/finance/summary', [FinanceController::class, 'summary']);
        Route::get('/finance/outstanding-balances', [FinanceController::class, 'outstandingBalances']);
        Route::get('/finance/aging', [FinanceController::class, 'aging']);
        Route::get('/finance/reconciliation', [FinanceController::class, 'reconciliation']);
        Route::get('/finance/cash-flow', [FinanceController::class, 'cashFlow']);
        Route::get('/finance/reports/{period}', [FinanceController::class, 'periodReport']);
    });

    // People & enrollment
    Route::get('/parents/{id}/children', [ParentController::class, 'children']);

    // Student portal (scoped to the authenticated linked student).
    Route::prefix('student-portal')->group(function () {
        Route::get('/dashboard', [StudentPortalController::class, 'dashboard']);
        Route::get('/me', [StudentPortalController::class, 'me']);
        Route::get('/attendance', [StudentPortalController::class, 'attendance']);
        Route::get('/grades', [StudentPortalController::class, 'grades']);
        Route::get('/fees', [StudentPortalController::class, 'fees']);
        Route::get('/timetable', [StudentPortalController::class, 'timetable']);
        Route::get('/assignments', [StudentPortalController::class, 'assignments']);
        Route::get('/assignments/{id}', [StudentPortalController::class, 'showAssignment']);
        Route::post('/assignments/{id}/submit', [StudentPortalController::class, 'submitAssignment']);
        Route::get('/announcements', [StudentPortalController::class, 'announcements']);
        Route::get('/cbt/available', [StudentPortalController::class, 'cbtAvailable']);
        Route::post('/cbt/start', [StudentPortalController::class, 'startCbt']);
        Route::get('/cbt/sessions/{id}', [StudentPortalController::class, 'showCbtSession']);
        Route::post('/cbt/sessions/{id}/submit', [StudentPortalController::class, 'submitCbt']);
        Route::post('/cbt/sessions/{id}/anti-cheat', [StudentPortalController::class, 'cbtAntiCheat']);
    });

    // Parent / guardian portal
    Route::prefix('parent/portal')->group(function () {
        Route::get('/dashboard', [ParentPortalController::class, 'dashboard']);
        Route::get('/children', [ParentPortalController::class, 'children']);
        Route::get('/students/{studentId}/results', [ParentPortalController::class, 'results']);
        Route::get('/students/{studentId}/attendance', [ParentPortalController::class, 'attendance']);
        Route::get('/students/{studentId}/fees', [ParentPortalController::class, 'fees']);
        Route::get('/students/{studentId}/discipline', [ParentPortalController::class, 'discipline']);
        Route::get('/students/{studentId}/progress', [ParentPortalController::class, 'progress']);
        Route::get('/announcements', [ParentPortalController::class, 'announcements']);
        Route::get('/notifications', [ParentPortalController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [ParentPortalController::class, 'markNotificationRead']);
        Route::post('/notifications/read-all', [ParentPortalController::class, 'markAllNotificationsRead']);
        Route::get('/communications/threads', [ParentPortalController::class, 'threads']);
        Route::post('/communications/threads', [ParentPortalController::class, 'createThread']);
        Route::patch('/communications/threads/{threadId}', [ParentPortalController::class, 'updateThread']);
        Route::get('/communications/threads/{threadId}/messages', [ParentPortalController::class, 'threadMessages']);
        Route::post('/communications/threads/{threadId}/messages', [ParentPortalController::class, 'sendMessage']);
        Route::get('/store/items', [ParentPortalController::class, 'storeItems']);
        Route::post('/store/buy', [ParentPortalController::class, 'buyStoreItems']);
        Route::get('/trips', [ParentPortalController::class, 'trips']);
        Route::post('/trips/{id}/enroll', [ParentPortalController::class, 'enrollTrip']);
    });

    Route::get('/disciplinary-records', [DisciplinaryRecordController::class, 'index']);
    Route::post('/disciplinary-records', [DisciplinaryRecordController::class, 'store']);
    Route::get('/disciplinary-records/{id}', [DisciplinaryRecordController::class, 'show']);
    Route::put('/disciplinary-records/{id}', [DisciplinaryRecordController::class, 'update']);

    Route::get('/communications/threads', [CommunicationController::class, 'index']);
    Route::get('/communications/threads/unread-count', [CommunicationController::class, 'unreadCount']);
    Route::get('/communications/parents', [CommunicationController::class, 'parents']);
    Route::get('/communications/parents/{parentUserId}/students', [CommunicationController::class, 'parentStudents']);
    Route::get('/communications/staff', [CommunicationController::class, 'staff']);
    Route::post('/communications/threads', [CommunicationController::class, 'store']);
    Route::get('/communications/threads/{id}', [CommunicationController::class, 'show']);
    Route::patch('/communications/threads/{id}', [CommunicationController::class, 'update']);
    Route::post('/communications/threads/{id}/messages', [CommunicationController::class, 'reply']);

    Route::get('/guardians', [GuardianController::class, 'index']);
    Route::get('/guardians/{id}', [GuardianController::class, 'show']);
    Route::get('/guardians/{id}/students', [GuardianController::class, 'students']);
    Route::post('/guardians', [GuardianController::class, 'store']);
    Route::put('/guardians/{id}', [GuardianController::class, 'update']);
    Route::delete('/guardians/{id}', [GuardianController::class, 'destroy']);
    Route::post('/guardians/{id}/link-student', [GuardianController::class, 'linkToStudent']);
    Route::get('/enrollment-applications', [EnrollmentController::class, 'index']);
    Route::post('/enrollment-applications', [EnrollmentController::class, 'store']);
    Route::get('/enrollment-applications/{id}', [EnrollmentController::class, 'show']);
    Route::put('/enrollment-applications/{id}/approve', [EnrollmentController::class, 'approve']);
    Route::put('/enrollment-applications/{id}/reject', [EnrollmentController::class, 'reject']);
    Route::get('/leave-types', [LeaveTypeController::class, 'index']);
    Route::get('/leave-types/{id}', [LeaveTypeController::class, 'show']);
    Route::post('/leave-types', [LeaveTypeController::class, 'store']);
    Route::put('/leave-types/{id}', [LeaveTypeController::class, 'update']);
    Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy']);
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::post('/leave-requests/{id}/approve', [LeaveRequestController::class, 'approve']);
    Route::post('/leave-requests/{id}/reject', [LeaveRequestController::class, 'reject']);
    Route::get('/designations', [DesignationController::class, 'index']);
    Route::get('/designations/{id}', [DesignationController::class, 'show']);
    Route::post('/designations', [DesignationController::class, 'store']);
    Route::put('/designations/{id}', [DesignationController::class, 'update']);
    Route::delete('/designations/{id}', [DesignationController::class, 'destroy']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    Route::get('/staff-attendance', [StaffAttendanceController::class, 'index']);
    Route::get('/staff-attendance/roster', [StaffAttendanceController::class, 'roster']);
    Route::get('/staff-attendance/summary', [StaffAttendanceController::class, 'summary']);
    Route::post('/staff-attendance', [StaffAttendanceController::class, 'store']);
    Route::get('/student-categories', [StudentCategoryController::class, 'index']);
    Route::get('/student-categories/{id}', [StudentCategoryController::class, 'show']);
    Route::post('/student-categories', [StudentCategoryController::class, 'store']);
    Route::put('/student-categories/{id}', [StudentCategoryController::class, 'update']);
    Route::delete('/student-categories/{id}', [StudentCategoryController::class, 'destroy']);
    Route::get('/certificate-templates', [CertificateTemplateController::class, 'index']);
    Route::get('/certificate-templates/{id}', [CertificateTemplateController::class, 'show']);
    Route::post('/certificate-templates', [CertificateTemplateController::class, 'store']);
    Route::put('/certificate-templates/{id}', [CertificateTemplateController::class, 'update']);
    Route::delete('/certificate-templates/{id}', [CertificateTemplateController::class, 'destroy']);
    Route::post('/certificate-templates/{id}/issue', [CertificateTemplateController::class, 'issue']);
    Route::get('/school-certificates', [SchoolCertificateController::class, 'index']);
    Route::get('/school-certificates/{id}/download', [SchoolCertificateController::class, 'download']);
    Route::post('/school-certificates/{id}/revoke', [SchoolCertificateController::class, 'revoke']);
    Route::get('/school-certificates/{id}', [SchoolCertificateController::class, 'show']);
    Route::get('/school-currencies', [SchoolCurrencyController::class, 'index']);
    Route::get('/school-currencies/{id}', [SchoolCurrencyController::class, 'show']);
    Route::post('/school-currencies', [SchoolCurrencyController::class, 'store']);
    Route::put('/school-currencies/{id}', [SchoolCurrencyController::class, 'update']);
    Route::delete('/school-currencies/{id}', [SchoolCurrencyController::class, 'destroy']);
    Route::get('/school-languages', [SchoolLanguageController::class, 'index']);
    Route::get('/school-languages/{id}', [SchoolLanguageController::class, 'show']);
    Route::post('/school-languages', [SchoolLanguageController::class, 'store']);
    Route::put('/school-languages/{id}', [SchoolLanguageController::class, 'update']);
    Route::delete('/school-languages/{id}', [SchoolLanguageController::class, 'destroy']);

    // Reports & announcements
    Route::get('/reports/export', [ReportController::class, 'export']);
    Route::get('/reports/templates', [ReportController::class, 'templates']);
    Route::get('/reports/academic-performance', [ReportController::class, 'academicPerformance']);
    Route::get('/reports/attendance', [ReportController::class, 'attendance']);
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/class/{id}', [ReportController::class, 'classReport']);
    Route::get('/reports/{reportType}', [ReportController::class, 'generate']);
    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

    // School ERP modules
    Route::get('/analytics/insights', [AnalyticsController::class, 'index']);
    Route::get('/workflows/pending', [WorkflowController::class, 'pending']);
    Route::get('/workflows/history', [WorkflowController::class, 'history']);
    Route::get('/workflows/{id}', [WorkflowController::class, 'show']);
    Route::post('/workflows/{id}/approve', [WorkflowController::class, 'approve']);
    Route::post('/workflows/{id}/reject', [WorkflowController::class, 'reject']);
    Route::get('/procurement/requisitions', [ProcurementController::class, 'requisitions']);
    Route::post('/procurement/requisitions', [ProcurementController::class, 'storeRequisition']);
    Route::put('/procurement/requisitions/{id}', [ProcurementController::class, 'updateRequisition']);
    Route::post('/procurement/requisitions/{id}/submit', [ProcurementController::class, 'submit']);
    Route::post('/procurement/requisitions/{id}/disburse', [ProcurementController::class, 'disburse']);
    Route::get('/procurement/vendors', [ProcurementController::class, 'vendors']);
    Route::post('/procurement/vendors', [ProcurementController::class, 'storeVendor']);
    Route::put('/procurement/vendors/{id}', [ProcurementController::class, 'updateVendor']);
    Route::post('/procurement/goods-receipts', [ProcurementController::class, 'receiveGoods']);
    Route::get('/assets', [AssetController::class, 'index']);
    Route::post('/assets', [AssetController::class, 'store']);
    Route::put('/assets/{id}', [AssetController::class, 'update']);
    Route::post('/assets/{id}/maintenance', [AssetController::class, 'logMaintenance']);
    Route::post('/assets/{id}/dispose', [AssetController::class, 'dispose']);
    Route::get('/transport/vehicles', [TransportController::class, 'vehicles']);
    Route::post('/transport/vehicles', [TransportController::class, 'storeVehicle']);
    Route::put('/transport/vehicles/{id}', [TransportController::class, 'updateVehicle']);
    Route::get('/transport/drivers', [TransportController::class, 'drivers']);
    Route::post('/transport/drivers', [TransportController::class, 'storeDriver']);
    Route::put('/transport/drivers/{id}', [TransportController::class, 'updateDriver']);
    Route::get('/transport/routes', [TransportController::class, 'routes']);
    Route::post('/transport/routes', [TransportController::class, 'storeRoute']);
    Route::put('/transport/routes/{id}', [TransportController::class, 'updateRoute']);
    Route::post('/transport/allocations', [TransportController::class, 'allocateStudent']);
    Route::get('/hostels', [HostelController::class, 'index']);
    Route::post('/hostels', [HostelController::class, 'store']);
    Route::put('/hostels/{id}', [HostelController::class, 'update']);
    Route::post('/hostels/{id}/rooms', [HostelController::class, 'storeRoom']);
    Route::post('/hostels/allocations', [HostelController::class, 'allocate']);
    Route::get('/library/books', [LibraryController::class, 'books'])->middleware('capability:canManageLibrary,isStaff');
    Route::post('/library/books', [LibraryController::class, 'storeBook'])->middleware('capability:canManageLibrary');
    Route::put('/library/books/{id}', [LibraryController::class, 'updateBook'])->middleware('capability:canManageLibrary');
    Route::delete('/library/books/{id}', [LibraryController::class, 'destroyBook'])->middleware('capability:canManageLibrary');
    Route::get('/library/loans', [LibraryController::class, 'loans'])->middleware('capability:canManageLibrary,isStaff');
    Route::post('/library/loans', [LibraryController::class, 'borrow'])->middleware('capability:canManageLibrary');
    Route::post('/library/loans/{id}/return', [LibraryController::class, 'returnBook']);
    Route::get('/library/members', [LibraryMemberController::class, 'index']);
    Route::get('/library/members/{id}', [LibraryMemberController::class, 'show']);
    Route::post('/library/members', [LibraryMemberController::class, 'store']);
    Route::put('/library/members/{id}', [LibraryMemberController::class, 'update']);
    Route::delete('/library/members/{id}', [LibraryMemberController::class, 'destroy']);
    Route::get('/visitors', [VisitorController::class, 'index']);
    Route::post('/visitors', [VisitorController::class, 'checkIn']);
    Route::post('/visitors/check-in', [VisitorController::class, 'checkIn']);
    Route::post('/visitors/{id}/check-out', [VisitorController::class, 'checkOut']);
    Route::get('/help-desk/tickets', [HelpDeskController::class, 'index']);
    Route::post('/help-desk/tickets', [HelpDeskController::class, 'store']);
    Route::put('/help-desk/tickets/{id}', [HelpDeskController::class, 'update']);
    Route::post('/help-desk/tickets/{id}/resolve', [HelpDeskController::class, 'resolve']);
    Route::post('/help-desk/tickets/{id}/close', [HelpDeskController::class, 'close']);
    Route::get('/recruitment/jobs', [RecruitmentController::class, 'jobs']);
    Route::post('/recruitment/jobs', [RecruitmentController::class, 'storeJob']);
    Route::put('/recruitment/jobs/{id}', [RecruitmentController::class, 'updateJob']);
    Route::post('/recruitment/jobs/{id}/close', [RecruitmentController::class, 'closeJob']);
    Route::get('/recruitment/applications', [RecruitmentController::class, 'applications']);
    Route::post('/recruitment/applications', [RecruitmentController::class, 'storeApplication']);
    Route::put('/recruitment/applications/{id}', [RecruitmentController::class, 'updateApplication']);
    Route::post('/recruitment/applications/{id}/shortlist', [RecruitmentController::class, 'shortlistApplication']);
    Route::post('/recruitment/applications/{id}/interview', [RecruitmentController::class, 'interviewApplication']);
    Route::post('/recruitment/applications/{id}/offer', [RecruitmentController::class, 'offerApplication']);
    Route::post('/recruitment/applications/{id}/hire', [RecruitmentController::class, 'hireApplication']);
    Route::post('/recruitment/applications/{id}/reject', [RecruitmentController::class, 'rejectApplication']);
    Route::get('/health/students/{id}/profile', [HealthController::class, 'profile']);
    Route::put('/health/students/{id}/profile', [HealthController::class, 'updateProfile']);
    Route::get('/health/clinic-visits', [HealthController::class, 'visits']);
    Route::post('/health/clinic-visits', [HealthController::class, 'recordVisit']);
    Route::put('/health/clinic-visits/{id}', [HealthController::class, 'updateVisit']);
    Route::get('/events', [SchoolEventController::class, 'index']);
    Route::post('/events', [SchoolEventController::class, 'store']);
    Route::put('/events/{id}', [SchoolEventController::class, 'update']);
    Route::delete('/events/{id}', [SchoolEventController::class, 'destroy']);
    Route::get('/compliance/policies', [ComplianceController::class, 'policies']);
    Route::post('/compliance/policies', [ComplianceController::class, 'storePolicy']);
    Route::put('/compliance/policies/{id}', [ComplianceController::class, 'updatePolicy']);
    Route::get('/compliance/incidents', [ComplianceController::class, 'incidents']);
    Route::post('/compliance/incidents', [ComplianceController::class, 'storeIncident']);
    Route::put('/compliance/incidents/{id}', [ComplianceController::class, 'updateIncident']);
    Route::get('/consent-forms', [ConsentFormController::class, 'index']);
    Route::post('/consent-forms', [ConsentFormController::class, 'store']);
    Route::put('/consent-forms/{id}', [ConsentFormController::class, 'update']);
    Route::get('/parent/portal/consent-forms', [ConsentFormController::class, 'parentForms']);
    Route::post('/parent/portal/consent-forms/{id}/respond', [ConsentFormController::class, 'respond']);

    // Audit trail
    Route::get('/audit-logs/login-history', [AuditLogController::class, 'loginHistory']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);

    // Advanced platform features
    Route::prefix('platform')->group(function () {
        Route::get('/policy-rules', [PlatformCoreController::class, 'policyRules']);
        Route::post('/policy-rules', [PlatformCoreController::class, 'storePolicyRule']);
        Route::post('/policy-rules/evaluate', [PlatformCoreController::class, 'evaluatePolicy']);
        Route::get('/workflow-definitions', [PlatformCoreController::class, 'workflowDefinitions']);
        Route::post('/workflow-definitions', [PlatformCoreController::class, 'saveWorkflowDefinition']);
        Route::get('/branches/tree', [PlatformCoreController::class, 'branchTree']);
        Route::post('/branches', [PlatformCoreController::class, 'createBranch']);
        Route::get('/retention-policies', [PlatformCoreController::class, 'retentionPolicies']);
        Route::post('/retention-policies', [PlatformCoreController::class, 'storeRetentionPolicy']);
        Route::get('/masking-rules', [PlatformCoreController::class, 'maskingRules']);
        Route::post('/masking-rules', [PlatformCoreController::class, 'storeMaskingRule']);
        Route::get('/students/{id}/versions', [PlatformCoreController::class, 'studentVersions']);

        Route::post('/communications/send', [PlatformCommunicationController::class, 'send']);
        Route::get('/communications/tracking', [PlatformCommunicationController::class, 'tracking']);
        Route::post('/communications/deliveries/{id}/read', [PlatformCommunicationController::class, 'markRead']);

        Route::get('/documents', [PlatformDocumentController::class, 'documents']);
        Route::post('/documents', [PlatformDocumentController::class, 'createDocument']);
        Route::post('/documents/{id}/sign', [PlatformDocumentController::class, 'signDocument']);
        Route::get('/certificates', [PlatformDocumentController::class, 'certificates']);
        Route::post('/certificates/issue', [PlatformDocumentController::class, 'issueCertificate']);
        Route::get('/vault', [PlatformDocumentController::class, 'vaultIndex']);
        Route::post('/vault', [PlatformDocumentController::class, 'vaultStore']);
        Route::get('/vault/{id}', [PlatformDocumentController::class, 'vaultRetrieve']);

        Route::get('/scholarships', [PlatformFinanceController::class, 'scholarships']);
        Route::post('/scholarships', [PlatformFinanceController::class, 'storeScholarship']);
        Route::post('/scholarships/{id}/apply', [PlatformFinanceController::class, 'applyScholarship']);
        Route::post('/scholarship-applications/{id}/review', [PlatformFinanceController::class, 'reviewScholarshipApplication']);
        Route::get('/payment-gateways', [PlatformFinanceController::class, 'gatewayConfigs']);
        Route::post('/payment-gateways', [PlatformFinanceController::class, 'storeGatewayConfig']);
        Route::post('/payments/initiate', [PlatformFinanceController::class, 'initiatePayment']);
        Route::get('/payments/status/{reference}', [PaymentWebhookController::class, 'status']);
        Route::get('/fee-penalty-rules', [PlatformFinanceController::class, 'penaltyRules']);
        Route::post('/fee-penalty-rules', [PlatformFinanceController::class, 'storePenaltyRule']);
        Route::get('/refunds', [PlatformFinanceController::class, 'refunds']);
        Route::post('/refunds', [PlatformFinanceController::class, 'requestRefund']);
        Route::post('/refunds/{refund}/approve', [PlatformFinanceController::class, 'approveRefund']);

        Route::get('/behavior-points', [PlatformStaffController::class, 'behaviorPoints']);
        Route::post('/behavior-points', [PlatformStaffController::class, 'storeBehaviorPoint']);
        Route::get('/behavior-points/students/{studentId}/summary', [PlatformStaffController::class, 'behaviorSummary']);
        Route::get('/interventions', [PlatformStaffController::class, 'interventions']);
        Route::post('/interventions', [PlatformStaffController::class, 'storeIntervention']);
        Route::get('/staff-tasks', [PlatformStaffController::class, 'tasks']);
        Route::post('/staff-tasks', [PlatformStaffController::class, 'storeTask']);
        Route::put('/staff-tasks/{id}', [PlatformStaffController::class, 'updateTask']);
        Route::get('/staff-feed', [PlatformStaffController::class, 'feed']);
        Route::post('/staff-feed', [PlatformStaffController::class, 'postFeed']);
        Route::post('/staff-feed/{id}/comments', [PlatformStaffController::class, 'commentFeed']);

        Route::get('/operations/live', [PlatformOperationsController::class, 'liveDashboard']);
        Route::post('/operations/alerts/{id}/resolve', [PlatformOperationsController::class, 'resolveAlert']);
        Route::get('/analytics/predictive', [PlatformOperationsController::class, 'predictiveAnalytics']);
        Route::get('/system/health', [PlatformOperationsController::class, 'systemHealth']);
        Route::get('/audit-integrity/verify', [PlatformOperationsController::class, 'verifyAuditIntegrity']);
        Route::get('/api-clients', [PlatformOperationsController::class, 'apiClients']);
        Route::post('/api-clients', [PlatformOperationsController::class, 'createApiClient']);
    });

    // Enterprise ERP modules (16-module vision)
    Route::prefix('enterprise')->group(function () {
        Route::get('/command-center', [EnterpriseIntelligenceController::class, 'commandCenter']);

        Route::get('/academic/curriculum', [EnterpriseAcademicController::class, 'curriculum']);
        Route::post('/academic/curriculum', [EnterpriseAcademicController::class, 'publishCurriculum']);
        Route::post('/academic/learning-outcomes', [EnterpriseAcademicController::class, 'recordOutcome']);
        Route::get('/academic/assessment-categories', [EnterpriseAcademicController::class, 'assessmentCategories']);
        Route::post('/academic/assessment-categories', [EnterpriseAcademicController::class, 'storeAssessmentCategory']);
        Route::post('/academic/continuous-assessments', [EnterpriseAcademicController::class, 'recordAssessment']);
        Route::get('/academic/students/{studentId}/subjects/{subjectId}/weighted-grade', [EnterpriseAcademicController::class, 'weightedGrade']);
        Route::get('/academic/gpa-ranking', [EnterpriseAcademicController::class, 'gpaRanking']);
        Route::post('/academic/gradebook-rules', [EnterpriseAcademicController::class, 'storeGradebookRule']);
        Route::get('/academic/students/{studentId}/subjects/{subjectId}/prerequisites', [EnterpriseAcademicController::class, 'checkPrerequisites']);
        Route::get('/academic/promotion-rules', [EnterpriseAcademicController::class, 'promotionRules']);
        Route::post('/academic/promotion-rules', [EnterpriseAcademicController::class, 'storePromotionRule']);
        Route::get('/academic/students/{studentId}/promotion-evaluation', [EnterpriseAcademicController::class, 'evaluatePromotion']);
        Route::get('/academic/calendar', [EnterpriseAcademicController::class, 'calendar']);
        Route::post('/academic/calendar', [EnterpriseAcademicController::class, 'storeCalendarEntry']);
        Route::put('/academic/calendar/{id}', [EnterpriseAcademicController::class, 'updateCalendarEntry']);
        Route::delete('/academic/calendar/{id}', [EnterpriseAcademicController::class, 'destroyCalendarEntry']);

        Route::post('/finance/accounts/seed', [EnterpriseFinanceController::class, 'seedAccounts']);
        Route::get('/finance/accounts', [EnterpriseFinanceController::class, 'accounts']);
        Route::post('/finance/journals', [EnterpriseFinanceController::class, 'postJournal']);
        Route::get('/finance/exchange-rates', [EnterpriseFinanceController::class, 'exchangeRates']);
        Route::post('/finance/exchange-rates', [EnterpriseFinanceController::class, 'storeExchangeRate']);
        Route::get('/finance/instalment-plans', [EnterpriseFinanceController::class, 'instalmentPlans']);
        Route::post('/finance/instalment-plans', [EnterpriseFinanceController::class, 'createInstalmentPlan']);
        Route::get('/finance/bank-statements', [EnterpriseFinanceController::class, 'bankStatements']);
        Route::post('/finance/bank-statements', [EnterpriseFinanceController::class, 'importBankLine']);
        Route::post('/finance/bank-statements/{line}/reconcile', [EnterpriseFinanceController::class, 'reconcile']);
        Route::get('/finance/reports/profit-loss', [EnterpriseFinanceController::class, 'profitAndLoss']);
        Route::get('/finance/reports/balance-sheet', [EnterpriseFinanceController::class, 'balanceSheet']);
        Route::get('/finance/cashflow-forecast', [EnterpriseFinanceController::class, 'cashflowForecast']);
        Route::get('/finance/revenue-rules', [EnterpriseFinanceController::class, 'revenueRules']);
        Route::post('/finance/revenue-rules', [EnterpriseFinanceController::class, 'storeRevenueRule']);

        Route::get('/exams/question-bank', [EnterpriseExamController::class, 'questions'])->middleware('capability:canManageExaminations');
        Route::post('/exams/question-bank', [EnterpriseExamController::class, 'storeQuestion'])->middleware('capability:canManageExaminations');
        Route::post('/exams/generate-paper', [EnterpriseExamController::class, 'generatePaper'])->middleware('capability:canManageExaminations');
        Route::post('/exams/cbt/start', [EnterpriseExamController::class, 'startCbt'])->middleware('capability:canManageExaminations');
        Route::post('/exams/cbt/{id}/submit', [EnterpriseExamController::class, 'submitCbt'])->middleware('capability:canManageExaminations');
        Route::post('/exams/cbt/{sessionId}/anti-cheat', [EnterpriseExamController::class, 'antiCheatLog'])->middleware('capability:canManageExaminations');
        Route::get('/exams/remark-requests', [EnterpriseExamController::class, 'remarkRequests'])->middleware('capability:canManageExaminations');
        Route::post('/exams/remark-requests', [EnterpriseExamController::class, 'requestRemark'])->middleware('capability:canManageExaminations');
        Route::get('/workflows/delegations', [EnterpriseExamController::class, 'delegations'])->middleware('capability:canManageTeachers');
        Route::post('/workflows/delegations', [EnterpriseExamController::class, 'storeDelegation'])->middleware('capability:canManageTeachers');

        Route::post('/admissions/score', [EnterpriseIntelligenceController::class, 'admissionScore']);
        Route::get('/students/{id}/profile', [EnterpriseIntelligenceController::class, 'studentProfile']);
        Route::get('/students/{id}/timeline', [EnterpriseIntelligenceController::class, 'studentTimeline']);
        Route::post('/students/{id}/timeline', [EnterpriseIntelligenceController::class, 'recordTimelineEvent']);
        Route::get('/early-warnings', [EnterpriseIntelligenceController::class, 'earlyWarnings']);
        Route::get('/alumni', [EnterpriseIntelligenceController::class, 'alumni']);
        Route::post('/alumni', [EnterpriseIntelligenceController::class, 'storeAlumni']);
        Route::put('/alumni/{id}', [EnterpriseIntelligenceController::class, 'updateAlumni']);
        Route::delete('/alumni/{id}', [EnterpriseIntelligenceController::class, 'destroyAlumni']);
        Route::post('/alumni/{id}/engagements', [EnterpriseIntelligenceController::class, 'recordAlumniEngagement']);
        Route::post('/alumni/students/{studentId}', [EnterpriseIntelligenceController::class, 'registerAlumni']);
        Route::get('/campaigns', [EnterpriseIntelligenceController::class, 'campaigns']);
        Route::post('/campaigns', [EnterpriseIntelligenceController::class, 'storeCampaign']);
        Route::post('/campaigns/{id}/send', [EnterpriseIntelligenceController::class, 'sendCampaign']);

        Route::get('/hr/performance-reviews', [EnterpriseGovernanceController::class, 'performanceReviews']);
        Route::post('/hr/performance-reviews', [EnterpriseGovernanceController::class, 'storePerformanceReview']);
        Route::get('/hr/contracts', [EnterpriseGovernanceController::class, 'contracts']);
        Route::post('/hr/contracts', [EnterpriseGovernanceController::class, 'storeContract']);
        Route::get('/hr/certifications', [EnterpriseGovernanceController::class, 'certifications']);
        Route::post('/hr/certifications', [EnterpriseGovernanceController::class, 'storeCertification']);
        Route::get('/compliance/requirements', [EnterpriseGovernanceController::class, 'complianceRequirements']);
        Route::post('/compliance/requirements', [EnterpriseGovernanceController::class, 'storeComplianceRequirement']);
        Route::get('/integrations/connectors', [EnterpriseGovernanceController::class, 'connectors']);
        Route::post('/integrations/connectors', [EnterpriseGovernanceController::class, 'storeConnector']);
        Route::get('/integrations/webhooks', [EnterpriseGovernanceController::class, 'webhooks']);
        Route::post('/integrations/webhooks', [EnterpriseGovernanceController::class, 'storeWebhook']);
        Route::get('/integrations/webhooks/deliveries', [EnterpriseGovernanceController::class, 'webhookDeliveries']);
        Route::post('/integrations/webhooks/deliveries/{id}/retry', [EnterpriseGovernanceController::class, 'retryWebhookDelivery']);
        Route::put('/integrations/webhooks/{id}', [EnterpriseGovernanceController::class, 'updateWebhook']);
        Route::delete('/integrations/webhooks/{id}', [EnterpriseGovernanceController::class, 'destroyWebhook']);
        Route::get('/security/abac-policies', [EnterpriseGovernanceController::class, 'abacPolicies']);
        Route::post('/security/abac-policies', [EnterpriseGovernanceController::class, 'storeAbacPolicy']);
        Route::get('/group/policies', [EnterpriseGovernanceController::class, 'groupPolicies']);
        Route::post('/group/policies', [EnterpriseGovernanceController::class, 'storeGroupPolicy']);
        Route::get('/group/transfers', [EnterpriseGovernanceController::class, 'crossSchoolTransfers']);
        Route::post('/group/transfers', [EnterpriseGovernanceController::class, 'requestTransfer']);
    });
});
