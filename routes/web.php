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
use App\Http\Controllers\Staff\StaffChatController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Public Landing ───────────────────────────────────────────────────────────

Route::get('/', fn () => Inertia::render('Welcome'))->name('home');
Route::get('/logo.png', fn () => response()->file(public_path('logo.png')));
Route::get('/uat_beneficiaries_sample.csv', function () {
    $csv = "listahanan_id,first_name,last_name,middle_name,suffix,birthdate,sex,civil_status,contact_number,house_no,street,purok,barangay,enrollment_date,remarks,member_1_first_name,member_1_last_name,member_1_middle_name,member_1_birthdate,member_1_sex,member_1_relationship,member_1_education_level,member_1_school_name,member_2_first_name,member_2_last_name,member_2_birthdate,member_2_sex,member_2_relationship,member_2_education_level,member_2_school_name,member_3_first_name,member_3_last_name,member_3_birthdate,member_3_sex,member_3_relationship,member_3_education_level,member_3_school_name\n" .
           "NHTS-LIPA-2026-001,Maria,Santos,Reyes,,1985-04-12,female,married,09171234561,123,P. Torres St.,Purok 1,Sabang,2022-01-15,Qualified 4Ps Household,Juan,Santos,Reyes,2010-06-15,male,child,junior_high,Sabang National High School,Ana,Santos,Reyes,2014-08-20,female,child,elementary,Sabang Elementary School,Pedro,Santos,Reyes,2021-02-10,male,child,daycare,Sabang Child Development Center\n" .
           "NHTS-LIPA-2026-002,Juana,Dela Cruz,Mendoza,,1990-09-25,female,married,09182345672,45,CM Recto Ave,Purok 2,Marawoy,2022-03-10,Active 4Ps Member,Mark,Dela Cruz,Mendoza,2009-11-12,male,child,senior_high,Lipa City National High School,Sofia,Dela Cruz,Mendoza,2015-04-05,female,child,elementary,Marawoy Elementary School,Gabriel,Dela Cruz,Mendoza,2020-07-19,male,child,daycare,Marawoy Daycare Center\n" .
           "NHTS-LIPA-2026-003,Corazon,Aquino,Rizal,,1988-11-30,female,widowed,09193456783,78,M.K. Lina St.,Purok 3,Tambo,2021-08-01,Solo Parent Household,Carlo,Aquino,Rizal,2008-02-14,male,child,senior_high,Tambo National High School,Bea,Aquino,Rizal,2012-10-18,female,child,elementary,Tambo Elementary School,,,\n" .
           "NHTS-LIPA-2026-004,Elena,Reyes,Gonzales,,1992-03-14,female,married,09204567894,12,President Laurel Highway,Purok 4,Bolbok,2023-02-20,Compliant Household,Daniel,Reyes,Gonzales,2013-07-22,male,child,elementary,Bolbok Elementary School,Grace,Reyes,Gonzales,2017-09-30,female,child,elementary,Bolbok Elementary School,Mateo,Reyes,Gonzales,2022-05-11,male,child,daycare,Bolbok Child Center\n" .
           "NHTS-LIPA-2026-005,Rosa,Villanueva,Mercado,,1984-07-08,female,married,09215678905,56,Gen. Luna St.,Purok 1,Lodlod,2020-11-12,Pre-qualified Listahanan,Joshua,Villanueva,Mercado,2007-12-04,male,child,senior_high,Lipa City Science High School,Chloe,Villanueva,Mercado,2011-05-17,female,child,junior_high,Lodlod National High School,Lucas,Villanueva,Mercado,2016-08-25,male,child,elementary,Lodlod Elementary School\n" .
           "NHTS-LIPA-2026-006,Teresa,Castillo,Bautista,,1991-12-19,female,married,09226789016,89,A. Mabini St.,Purok 5,Mataas na Lupa,2022-06-18,Active Member,Christian,Castillo,Bautista,2014-01-09,male,child,elementary,Mataas na Lupa Elem School,Mia,Castillo,Bautista,2018-06-21,female,child,daycare,Mataas na Lupa Daycare,,,\n" .
           "NHTS-LIPA-2026-007,Lourdes,Bautista,Alvarez,,1987-05-02,female,married,09237890127,101,J.P. Laurel St.,Purok 2,Banaybanay,2021-04-25,Compliant 4Ps Household,Angelo,Bautista,Alvarez,2010-09-14,male,child,junior_high,Banaybanay National HS,Samantha,Bautista,Alvarez,2015-11-03,female,child,elementary,Banaybanay Elementary School,,,\n" .
           "NHTS-LIPA-2026-008,Carmela,Ramos,Torres,,1989-10-15,female,married,09248901238,234,Katipunan St.,Purok 3,Antipolo del Norte,2022-09-01,Verified Household,Adrian,Ramos,Torres,2011-03-29,male,child,junior_high,Antipolo HS,Angelica,Ramos,Torres,2016-01-14,female,child,elementary,Antipolo Elementary School,Ethan,Ramos,Torres,2021-11-08,male,child,daycare,Antipolo Daycare\n" .
           "NHTS-LIPA-2026-009,Divina,Garcia,Fernandez,,1993-01-22,female,separated,09259012349,67,San Jose St.,Purok 1,Kayumanggi,2023-01-10,Solo Parent,John,Garcia,Fernandez,2012-08-08,male,child,elementary,Kayumanggi Elem School,Hannah,Garcia,Fernandez,2017-04-12,female,child,elementary,Kayumanggi Elem School,,,\n" .
           "NHTS-LIPA-2026-010,Esperanza,Navarro,Cruz,,1986-06-11,female,married,09260123450,150,Lipa-Ibaan Rd,Purok 4,Inosloban,2020-05-15,Active Listahanan 4Ps,Rafael,Navarro,Cruz,2009-04-30,male,child,junior_high,Inosloban-Marawoy National HS,Patricia,Navarro,Cruz,2013-10-22,female,child,elementary,Inosloban Elementary School,Simon,Navarro,Cruz,2019-03-17,male,child,daycare,Inosloban Daycare Center\n";

    return response($csv, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="uat_beneficiaries_sample.csv"',
    ]);
});

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
        Route::post('beneficiaries/batch-download-pdf', [SuperAdminBeneficiaryController::class, 'batchDownloadCards'])->name('beneficiaries.cards.batch-download');

        // Beneficiary CRUD resource
        Route::resource('beneficiaries',        SuperAdminBeneficiaryController::class);

        // Per-beneficiary actions (these use {id} so they're fine after resource)
        Route::post('beneficiaries/{id}/card',         [SuperAdminBeneficiaryController::class, 'issueCard'])->name('beneficiaries.card.issue');
        Route::get('beneficiaries/{id}/card/download',  [SuperAdminBeneficiaryController::class, 'downloadCard'])->name('beneficiaries.card.download');
        Route::get('beneficiaries/{id}/card/preview',   [SuperAdminBeneficiaryController::class, 'cardPreview'])->name('beneficiaries.card.preview');
        Route::post('beneficiaries/{id}/activate',      [SuperAdminBeneficiaryController::class, 'activate'])->name('beneficiaries.activate');



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

Route::middleware(['auth', 'role:admin_4ps,superadmin,barangay_assistant'])
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

Route::middleware(['auth', 'role:barangay_assistant,admin_4ps,superadmin'])
    ->prefix('barangay')
    ->name('barangay.')
    ->group(function () {
        Route::get('/dashboard',     [BarangayDashboardController::class, 'index'])->name('dashboard');
        Route::get('/scanner',       [FdsAttendanceController::class, 'scanner'])->name('scanner');
        Route::post('/scan',         [FdsAttendanceController::class, 'scan'])->name('scan');
        Route::get('/today-count',   [FdsAttendanceController::class, 'todayCount'])->name('today-count');
    });

// ─── Staff Chat (Superadmin, Admins, Barangay Assistants) ─────────────────────

Route::middleware(['auth', 'role:superadmin,admin,admin_swa,admin_4ps,barangay_assistant'])
    ->prefix('staff/chat')
    ->name('staff.chat.')
    ->group(function () {
        Route::get('/',                        [StaffChatController::class, 'index'])->name('index');
        Route::get('/messages/{contactId}',    [StaffChatController::class, 'fetchMessages'])->name('messages');
        Route::post('/send',                   [StaffChatController::class, 'send'])->name('send');
        Route::get('/unread-count',            [StaffChatController::class, 'unreadCount'])->name('unread-count');
    });

// ─── Beneficiary Portal ──────────────────────────────────────────────────────

Route::middleware(['auth', 'role:beneficiary'])
    ->prefix('portal')
    ->name('beneficiary.')
    ->group(function () {
        Route::get('/dashboard',                [BeneficiaryDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile',                  [BeneficiaryDashboardController::class, 'profile'])->name('profile');
        Route::get('/grants',                   [BeneficiaryDashboardController::class, 'grants'])->name('grants');
        Route::get('/family',                   [BeneficiaryDashboardController::class, 'family'])->name('family');
        Route::get('/notifications',            [BeneficiaryDashboardController::class, 'notifications'])->name('notifications');
        Route::patch('/notifications/{id}/read', [BeneficiaryDashboardController::class, 'markNotificationRead'])->name('notifications.read');
        Route::get('/compliance',               [BeneficiaryDashboardController::class, 'compliance'])->name('compliance');
    });
