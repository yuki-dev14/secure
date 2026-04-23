<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = SystemSetting::orderBy('group')->orderBy('key')->get()
            ->groupBy('group')
            ->map(fn($group) => $group->keyBy('key'));

        $systemInfo = [
            'php_version'     => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_driver'       => config('database.default'),
            'db_name'         => config('database.connections.' . config('database.default') . '.database'),
            'mail_driver'     => config('mail.default'),
            'queue_driver'    => config('queue.default'),
            'app_env'         => config('app.env'),
            'app_debug'       => config('app.debug') ? 'true' : 'false',
        ];

        return Inertia::render('Superadmin/Settings/Index', [
            'settings'   => $settings,
            'systemInfo' => $systemInfo,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'settings'   => 'required|array',
            'settings.*' => 'nullable|string|max:500',
        ]);

        $changed = [];

        foreach ($request->settings as $key => $value) {
            $setting = SystemSetting::find($key);
            if (!$setting) continue;

            // Mask password in audit log
            $displayValue = str_contains($key, 'password') ? '••••••••' : $value;
            if ($setting->value !== (string) $value) {
                $changed[$key] = ['from' => str_contains($key, 'password') ? '••••••••' : $setting->value, 'to' => $displayValue];
            }

            SystemSetting::set($key, $value ?? '');
        }

        if (!empty($changed)) {
            AuditLogService::log(
                'settings_updated',
                null,
                [],
                $changed,
                'System settings updated by superadmin'
            );
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            Mail::raw(
                "This is a test email from the SECURE 4Ps system.\n\n" .
                "If you received this, your SMTP configuration is working correctly.\n\n" .
                "Sent: " . now()->format('F d, Y h:i A'),
                function ($message) use ($request) {
                    $message->to($request->test_email)
                            ->subject('✅ SECURE 4Ps — SMTP Test Email');
                }
            );

            AuditLogService::log('settings_test_email', null, [], ['to' => $request->test_email], 'Test email sent');

            return back()->with('success', "Test email sent to {$request->test_email}. Check your inbox.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
