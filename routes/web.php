<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Superadmin\BeneficiaryController as SuperAdminBeneficiaryController;
use App\Http\Controllers\Superadmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\ReportController;
use App\Http\Controllers\Superadmin\ProxyController;
use App\Http\Controllers\Superadmin\SettingsController;
use App\Http\Controllers\Superadmin\BeneficiaryImportController;
use App\Http\Controllers\Superadmin\UserManagementController as SuperadminUserController;
use App\Http\Controllers\Superadmin\GrantComputationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BeneficiaryController as AdminBeneficiaryController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\AdminSwa\DashboardController as SwaDashboardController;
use App\Http\Controllers\AdminSwa\NonComplianceController;
use App\Http\Controllers\AdminSwa\ComplianceVerificationController;
use App\Http\Controllers\AdminSwa\GrantSummaryController;
use App\Http\Controllers\Admin4ps\DashboardController as FourPsDashboardController;
use App\Http\Controllers\Admin4ps\FdsAttendanceController;
use App\Http\Controllers\BarangayAssistant\DashboardController as BarangayDashboardController;
use App\Http\Controllers\Beneficiary\DashboardController as BeneficiaryDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Public Landing ───────────────────────────────────────────────────────────

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
Route::get('/logo.png', fn () => response()->file(public_path('logo.png')));

// ─── Authentication ───────────────────────────────────────────────────────────

// Staff Login (Superadmin, Admin, Verifier, Field Officer)
Route::middleware('guest')->group(function () {
    Route::get('/login',                [AuthController::class, 'showStaffLogin'])->name('staff.login');
    Route::post('/login',               [AuthController::class, 'staffLogin'])->name('staff.login.post');

    // Beneficiary Portal Login
    Route::get('/portal',               [AuthController::class, 'showBeneficiaryLogin'])->name('beneficiary.login');
    Route::post('/portal/login',        [AuthController::class, 'beneficiaryLogin'])->name('beneficiary.login.post');
    Route::post('/portal/qr-login',     [AuthController::class, 'beneficiaryQrLogin'])->name('beneficiary.qr-login.post');
});

Route::post('/logout',                  [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Beneficiary First-Login Password Change ──────────────────────────────────

Route::middleware(['auth'])->group(function () {
    Route::get('/portal/change-password',  [AuthController::class, 'showChangePassword'])->name('beneficiary.password.change');
    Route::post('/portal/change-password', [AuthController::class, 'updatePassword'])->name('beneficiary.password.update');
});

// ─── Beneficiary Portal ───────────────────────────────────────────────────────

Route::middleware(['auth', 'role:beneficiary', 'enforce.password.change'])
    ->prefix('portal')
    ->name('beneficiary.')
    ->group(function () {
        Route::get('/dashboard',    [BeneficiaryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile',      [BeneficiaryDashboardController::class, 'profile'])->name('profile');
        Route::get('/documents',    [BeneficiaryDashboardController::class, 'documents'])->name('documents');
        Route::get('/grants',       [BeneficiaryDashboardController::class, 'grants'])->name('grants');
        Route::get('/family',       [BeneficiaryDashboardController::class, 'family'])->name('family');
        Route::get('/notifications',[BeneficiaryDashboardController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{id}/read', [BeneficiaryDashboardController::class, 'markNotificationRead'])->name('notifications.read');

        // Document self-upload
        Route::post('/documents',                [BeneficiaryDashboardController::class, 'uploadDocument'])->name('documents.upload');
        Route::delete('/documents/{docId}',      [BeneficiaryDashboardController::class, 'deleteDocument'])->name('documents.delete');
    });

// ─── Superadmin ───────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard',                [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Bulk Import — must be declared BEFORE resource() to prevent
        // 'import' being matched as the {beneficiary} wildcard parameter.
        Route::get('beneficiaries/import',              [BeneficiaryImportController::class, 'index'])->name('beneficiaries.import');
        Route::post('beneficiaries/import',             [BeneficiaryImportController::class, 'store'])->name('beneficiaries.import.store');
        Route::get('beneficiaries/import/template',     [BeneficiaryImportController::class, 'template'])->name('beneficiaries.import.template');

        // Static batch routes — also before resource() for the same reason
        Route::post('beneficiaries/batch-cards',        [SuperAdminBeneficiaryController::class, 'batchIssueCards'])->name('beneficiaries.cards.batch');

        // Beneficiary CRUD resource
        Route::resource('beneficiaries',        SuperAdminBeneficiaryController::class);

        // Per-beneficiary actions (these use {id} so they're fine after resource)
        Route::post('beneficiaries/{id}/card',         [SuperAdminBeneficiaryController::class, 'issueCard'])->name('beneficiaries.card.issue');
        Route::get('beneficiaries/{id}/card/download',  [SuperAdminBeneficiaryController::class, 'downloadCard'])->name('beneficiaries.card.download');
        Route::get('beneficiaries/{id}/card/preview',   [SuperAdminBeneficiaryController::class, 'cardPreview'])->name('beneficiaries.card.preview');
        Route::post('beneficiaries/{id}/activate',      [SuperAdminBeneficiaryController::class, 'activate'])->name('beneficiaries.activate');

        // Proxy Management
        Route::post('beneficiaries/{beneficiary}/proxies',                 [ProxyController::class, 'store'])->name('beneficiaries.proxies.store');
        Route::put('beneficiaries/{beneficiary}/proxies/{proxy}',          [ProxyController::class, 'update'])->name('beneficiaries.proxies.update');
        Route::delete('beneficiaries/{beneficiary}/proxies/{proxy}',       [ProxyController::class, 'destroy'])->name('beneficiaries.proxies.destroy');
        Route::patch('beneficiaries/{beneficiary}/proxies/{proxy}/toggle', [ProxyController::class, 'toggleApproval'])->name('beneficiaries.proxies.toggle');

        // Audit Trail (Superadmin exclusive)
        Route::get('/audit-logs',               [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{id}',          [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::get('/audit-logs/export',        [AuditLogController::class, 'export'])->name('audit-logs.export');

        // System Settings
        Route::get('/settings',                [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings',                [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email',    [SettingsController::class, 'sendTestEmail'])->name('settings.test-email');

        // User management (with broader scope — all roles including beneficiaries)
        Route::get('/users',                    [SuperadminUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/toggle',    [SuperadminUserController::class, 'toggleActive'])->name('users.toggle');
        Route::patch('/beneficiaries/{beneficiary}/toggle-active', [SuperadminUserController::class, 'toggleBeneficiaryActive'])->name('beneficiaries.toggle-active');

        // Legacy superadmin.users routes kept for compatibility
        Route::resource('staff',               UserManagementController::class)->names('staff');

        // Reports & Exports
        Route::get('/reports',                          [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/beneficiaries',            [ReportController::class, 'beneficiaries'])->name('reports.beneficiaries');
        Route::get('/reports/beneficiaries/export',     [ReportController::class, 'exportBeneficiaries'])->name('reports.beneficiaries.export');
        Route::get('/reports/grants',                   [ReportController::class, 'grants'])->name('reports.grants');
        Route::get('/reports/grants/export',            [ReportController::class, 'exportGrants'])->name('reports.grants.export');

        // Grant Computation Tab
        Route::get('/grant-computation',                [GrantComputationController::class, 'index'])->name('grant-computation.index');
        Route::post('/grant-computation/compute',       [GrantComputationController::class, 'compute'])->name('grant-computation.compute');
        Route::get('/grant-computation/export',         [GrantComputationController::class, 'export'])->name('grant-computation.export');
    });

// ─── Admin ────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:admin,admin_4ps,admin_swa,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard',                [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports/dashboard-pdf',    [AdminReportController::class, 'dashboardPdf'])->name('reports.dashboard-pdf');

        // User / Staff Management
        Route::resource('users',                UserManagementController::class);
        Route::patch('users/{user}/toggle',     [UserManagementController::class, 'toggleActive'])->name('users.toggle');

        // Beneficiary records
        Route::get('beneficiaries',                                  [AdminBeneficiaryController::class, 'index'])->name('beneficiaries.index');
        Route::get('beneficiaries/{id}',                             [AdminBeneficiaryController::class, 'show'])->name('beneficiaries.show');
        Route::put('beneficiaries/{id}',                             [AdminBeneficiaryController::class, 'update'])->name('beneficiaries.update');
        Route::post('beneficiaries/{id}/activate',                   [AdminBeneficiaryController::class, 'activate'])->name('beneficiaries.activate');
        Route::post('beneficiaries/{id}/documents',                  [AdminBeneficiaryController::class, 'uploadDocument'])->name('beneficiaries.documents.upload');
        Route::delete('beneficiaries/{id}/documents/{docId}',        [AdminBeneficiaryController::class, 'deleteDocument'])->name('beneficiaries.documents.delete');
        Route::patch('beneficiaries/{id}/documents/{docId}/verify',  [AdminBeneficiaryController::class, 'verifyDocument'])->name('beneficiaries.documents.verify');
    });

// ─── Admin SWA (Health & Education Non-Compliance) ───────────────────────────

Route::middleware(['auth', 'role:admin_swa,superadmin'])
    ->prefix('adminswa')
    ->name('adminswa.')
    ->group(function () {
        Route::get('/dashboard',                              [SwaDashboardController::class, 'index'])->name('dashboard');

        // Non-compliance CRUD
        Route::get('/non-compliance',                         [NonComplianceController::class, 'index'])->name('non-compliance.index');
        Route::get('/non-compliance/create',                  [NonComplianceController::class, 'create'])->name('non-compliance.create');
        Route::post('/non-compliance',                        [NonComplianceController::class, 'store'])->name('non-compliance.store');

        // Confirm / Dismiss actions
        Route::patch('/non-compliance/{record}/confirm',      [NonComplianceController::class, 'confirm'])->name('non-compliance.confirm');
        Route::patch('/non-compliance/{record}/dismiss',      [NonComplianceController::class, 'dismiss'])->name('non-compliance.dismiss');
        Route::post('/non-compliance/batch',                  [NonComplianceController::class, 'batchProcess'])->name('non-compliance.batch');

        // Import (Google Forms / Excel)
        Route::get('/non-compliance/import',                  [NonComplianceController::class, 'importForm'])->name('non-compliance.import');
        Route::post('/non-compliance/import',                 [NonComplianceController::class, 'import'])->name('non-compliance.import.store');
        Route::get('/non-compliance/import/template',         [NonComplianceController::class, 'importTemplate'])->name('non-compliance.import.template');

        // Grant Summary & Computation
        Route::get('/grant-summary',                          [GrantSummaryController::class, 'index'])->name('grant-summary.index');
        Route::post('/grant-summary/compute',                 [GrantSummaryController::class, 'compute'])->name('grant-summary.compute');

        // Compliance Verification (Excel workflow — send to midwife/school rep, import back)
        Route::get('/compliance-verification',                [ComplianceVerificationController::class, 'index'])->name('compliance-verification.index');
        Route::post('/compliance-verification/send',          [ComplianceVerificationController::class, 'generateAndSend'])->name('compliance-verification.send');
        Route::get('/compliance-verification/template',       [ComplianceVerificationController::class, 'downloadTemplate'])->name('compliance-verification.template');
        Route::post('/compliance-verification/import',        [ComplianceVerificationController::class, 'importResults'])->name('compliance-verification.import');
        Route::get('/compliance-verification/report',         [ComplianceVerificationController::class, 'reportForSuperadmin'])->name('compliance-verification.report');
    });

// ─── Admin 4Ps (FDS Attendance Monitoring) ──────────────────────────────────

Route::middleware(['auth', 'role:admin_4ps,superadmin'])
    ->prefix('admin4ps')
    ->name('admin4ps.')
    ->group(function () {
        Route::get('/dashboard',                        [FourPsDashboardController::class, 'index'])->name('dashboard');

        // FDS Attendance
        Route::get('/fds-attendance',                   [FdsAttendanceController::class, 'index'])->name('fds.index');
        Route::get('/fds-attendance/scanner',            [FdsAttendanceController::class, 'scanner'])->name('fds.scanner');
        Route::post('/fds-attendance/scan',              [FdsAttendanceController::class, 'scan'])->name('fds.scan');
        Route::get('/fds-attendance/today-count',        [FdsAttendanceController::class, 'todayCount'])->name('fds.today-count');
        Route::post('/fds-attendance/report',            [FdsAttendanceController::class, 'reportToSuperadmin'])->name('fds.report');
    });

// ─── Barangay Assistant (FDS Scanner) ────────────────────────────────────────

Route::middleware(['auth', 'role:barangay_assistant'])
    ->prefix('barangay')
    ->name('barangay.')
    ->group(function () {
        Route::get('/dashboard',     [BarangayDashboardController::class, 'index'])->name('dashboard');
        Route::get('/scanner',       [FdsAttendanceController::class, 'scanner'])->name('scanner');
        Route::post('/scan',         [FdsAttendanceController::class, 'scan'])->name('scan');
        Route::get('/today-count',   [FdsAttendanceController::class, 'todayCount'])->name('today-count');
    });

// ─── Beneficiary Portal ──────────────────────────────────────────────────────

Route::middleware(['auth', 'role:beneficiary'])
    ->prefix('portal')
    ->name('beneficiary.')
    ->group(function () {
        Route::get('/dashboard',                [BeneficiaryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile',                  [BeneficiaryDashboardController::class, 'profile'])->name('profile');
        Route::get('/documents',                [BeneficiaryDashboardController::class, 'documents'])->name('documents');
        Route::post('/documents',               [BeneficiaryDashboardController::class, 'uploadDocument'])->name('documents.upload');
        Route::delete('/documents/{doc}',       [BeneficiaryDashboardController::class, 'deleteDocument'])->name('documents.delete');
        Route::get('/grants',                   [BeneficiaryDashboardController::class, 'grants'])->name('grants');
        Route::get('/family',                   [BeneficiaryDashboardController::class, 'family'])->name('family');
        Route::get('/notifications',            [BeneficiaryDashboardController::class, 'notifications'])->name('notifications');
        Route::patch('/notifications/{id}/read', [BeneficiaryDashboardController::class, 'markNotificationRead'])->name('notifications.read');
        Route::get('/compliance',               [BeneficiaryDashboardController::class, 'compliance'])->name('compliance');
    });
