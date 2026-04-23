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
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BeneficiaryController as AdminBeneficiaryController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\DistributionEventController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\ComplianceVerifier\DashboardController as VerifierDashboardController;
use App\Http\Controllers\ComplianceVerifier\ComplianceController;
use App\Http\Controllers\FieldOfficer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\FieldOfficer\ScannerController;
use App\Http\Controllers\FieldOfficer\DistributionController;
use App\Http\Controllers\FieldOfficer\ClaimHistoryController;
use App\Http\Controllers\Beneficiary\DashboardController as BeneficiaryDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Public Landing ───────────────────────────────────────────────────────────

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

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
        Route::get('/reports/compliance',               [ReportController::class, 'compliance'])->name('reports.compliance');
        Route::get('/reports/compliance/export',        [ReportController::class, 'exportCompliance'])->name('reports.compliance.export');
        Route::get('/reports/distributions',            [ReportController::class, 'distributions'])->name('reports.distributions');
        Route::get('/reports/distributions/export',     [ReportController::class, 'exportDistributions'])->name('reports.distributions.export');
        Route::get('/reports/grants',                   [ReportController::class, 'grants'])->name('reports.grants');
        Route::get('/reports/grants/export',            [ReportController::class, 'exportGrants'])->name('reports.grants.export');
    });

// ─── Admin ────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:admin,superadmin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard',                [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports/dashboard-pdf',    [AdminReportController::class, 'dashboardPdf'])->name('reports.dashboard-pdf');
        Route::get('/distribution-events/{distributionEvent}/report', [AdminReportController::class, 'eventReport'])->name('events.report');

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

        // Distribution Events
        Route::resource('distribution-events',  DistributionEventController::class)->names('events');
        Route::post('distribution-events/{event}/notify', [DistributionEventController::class, 'notifyBeneficiaries'])->name('events.notify');
        Route::post('distribution-events/{event}/compute', [DistributionEventController::class, 'batchComputeGrants'])->name('events.compute');
    });

// ─── Compliance Verifier ──────────────────────────────────────────────────────

Route::middleware(['auth', 'role:compliance_verifier,admin,superadmin'])
    ->prefix('verifier')
    ->name('verifier.')
    ->group(function () {
        Route::get('/dashboard',                [VerifierDashboardController::class, 'index'])->name('dashboard');
        Route::get('/beneficiaries',            [ComplianceController::class, 'index'])->name('beneficiaries');
        Route::get('/beneficiaries/{id}',       [ComplianceController::class, 'show'])->name('beneficiaries.show');
        Route::post('/beneficiaries/{id}/compliance', [ComplianceController::class, 'store'])->name('compliance.store');
        Route::put('/compliance/{record}',      [ComplianceController::class, 'update'])->name('compliance.update');
        Route::get('/compliance/periods',       [ComplianceController::class, 'periods'])->name('compliance.periods');
    });

// ─── Field Officer ────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:field_officer,admin,superadmin'])
    ->prefix('officer')
    ->name('officer.')
    ->group(function () {
        Route::get('/dashboard',                [OfficerDashboardController::class, 'index'])->name('dashboard');

        // QR Scanner
        Route::get('/scanner',                  [ScannerController::class, 'index'])->name('scanner');
        Route::post('/scanner/scan',            [ScannerController::class, 'scan'])->name('scanner.scan');

        // Distribution (claiming)
        Route::get('/distribution',             [DistributionController::class, 'index'])->name('distribution');
        Route::post('/distribution/release',    [DistributionController::class, 'release'])->name('distribution.release');
        Route::get('/distribution/{txn}',       [DistributionController::class, 'show'])->name('distribution.show');

        // Claim History
        Route::get('/claim-history',            [ClaimHistoryController::class, 'index'])->name('claim-history');
    });
