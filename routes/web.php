<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Superadmin\BeneficiaryController as SuperAdminBeneficiaryController;
use App\Http\Controllers\Superadmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\ReportController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\DistributionEventController;
use App\Http\Controllers\ComplianceVerifier\DashboardController as VerifierDashboardController;
use App\Http\Controllers\ComplianceVerifier\ComplianceController;
use App\Http\Controllers\FieldOfficer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\FieldOfficer\ScannerController;
use App\Http\Controllers\FieldOfficer\DistributionController;
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
    });

// ─── Superadmin ───────────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        Route::get('/dashboard',                [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Beneficiary Management
        Route::resource('beneficiaries',        SuperAdminBeneficiaryController::class);
        Route::post('beneficiaries/{id}/card',  [SuperAdminBeneficiaryController::class, 'issueCard'])->name('beneficiaries.card.issue');
        Route::get('beneficiaries/{id}/card/download', [SuperAdminBeneficiaryController::class, 'downloadCard'])->name('beneficiaries.card.download');
        Route::get('beneficiaries/{id}/card/preview',  [SuperAdminBeneficiaryController::class, 'cardPreview'])->name('beneficiaries.card.preview');

        // Audit Trail (Superadmin exclusive)
        Route::get('/audit-logs',               [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{id}',          [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::get('/audit-logs/export',        [AuditLogController::class, 'export'])->name('audit-logs.export');

        // User management (with broader scope)
        Route::resource('users',                UserManagementController::class);

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

        // User / Staff Management
        Route::resource('users',                UserManagementController::class);
        Route::patch('users/{user}/toggle',     [UserManagementController::class, 'toggleActive'])->name('users.toggle');

        // Beneficiary records
        Route::get('beneficiaries',             [SuperAdminBeneficiaryController::class, 'index'])->name('beneficiaries.index');
        Route::get('beneficiaries/{id}',        [SuperAdminBeneficiaryController::class, 'show'])->name('beneficiaries.show');
        Route::put('beneficiaries/{id}',        [SuperAdminBeneficiaryController::class, 'update'])->name('beneficiaries.update');

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
    });
