<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type')->default('string');  // string, boolean, integer, json
            $table->string('group')->default('general'); // general, mail, notifications, security
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        $now = now();
        DB::table('system_settings')->insert([
            // ── General ─────────────────────────────────────────────────────────
            ['key'=>'app_name',              'value'=>'SECURE 4Ps',                              'type'=>'string',  'group'=>'general',       'label'=>'Application Name',           'description'=>'Displayed in emails, headers and page titles.',                    'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'app_url',               'value'=>'http://localhost:8000',                   'type'=>'string',  'group'=>'general',       'label'=>'Application URL',            'description'=>'Full public URL — used in notification email links.',              'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'dswd_office',           'value'=>'DSWD — Lipa City Division, Batangas',    'type'=>'string',  'group'=>'general',       'label'=>'DSWD Office Name',           'description'=>'Shown in footers and printed cards.',                              'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'support_phone',         'value'=>'(043) XXX-XXXX',                         'type'=>'string',  'group'=>'general',       'label'=>'Support Phone Number',       'description'=>'Displayed in notification emails for beneficiary support.',        'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'maintenance_mode',      'value'=>'0',                                       'type'=>'boolean', 'group'=>'general',       'label'=>'Maintenance Mode',           'description'=>'When ON, only superadmins can log in.',                            'created_at'=>$now,'updated_at'=>$now],

            // ── Mail ────────────────────────────────────────────────────────────
            ['key'=>'mail_from_address',     'value'=>'noreply@secure4ps.dswd.gov.ph',           'type'=>'string',  'group'=>'mail',          'label'=>'From Address',               'description'=>'The sender email address shown to beneficiaries.',                 'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_from_name',        'value'=>'SECURE 4Ps — DSWD Lipa City',            'type'=>'string',  'group'=>'mail',          'label'=>'From Name',                  'description'=>'The sender name shown in email clients.',                          'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_mailer',           'value'=>'log',                                     'type'=>'string',  'group'=>'mail',          'label'=>'Mail Driver',                'description'=>'Use "log" for dev (writes to laravel.log), "smtp" for production.','created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_host',             'value'=>'smtp.gmail.com',                          'type'=>'string',  'group'=>'mail',          'label'=>'SMTP Host',                  'description'=>'e.g. smtp.gmail.com or sandbox.smtp.mailtrap.io',                  'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_port',             'value'=>'587',                                     'type'=>'integer', 'group'=>'mail',          'label'=>'SMTP Port',                  'description'=>'Usually 587 (TLS) or 465 (SSL).',                                  'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_encryption',       'value'=>'tls',                                     'type'=>'string',  'group'=>'mail',          'label'=>'Encryption',                 'description'=>'tls or ssl.',                                                      'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_username',         'value'=>'',                                        'type'=>'string',  'group'=>'mail',          'label'=>'SMTP Username',              'description'=>'Your email account or Mailtrap username.',                         'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'mail_password',         'value'=>'',                                        'type'=>'string',  'group'=>'mail',          'label'=>'SMTP Password',              'description'=>'App password or SMTP credential. Never share this.',               'created_at'=>$now,'updated_at'=>$now],

            // ── Notifications ────────────────────────────────────────────────────
            ['key'=>'notify_card_issued',     'value'=>'1',                                      'type'=>'boolean', 'group'=>'notifications', 'label'=>'Card Issued Alert',          'description'=>'Send email when a beneficiary\'s ID card is issued.',              'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'notify_compliance',      'value'=>'1',                                      'type'=>'boolean', 'group'=>'notifications', 'label'=>'Compliance Result Alert',    'description'=>'Send email when compliance is verified for a beneficiary.',        'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'notify_distribution',    'value'=>'1',                                      'type'=>'boolean', 'group'=>'notifications', 'label'=>'Distribution Schedule Alert','description'=>'Send email when a distribution event is scheduled.',               'created_at'=>$now,'updated_at'=>$now],

            // ── Security ────────────────────────────────────────────────────────
            ['key'=>'max_proxies_per_bene',  'value'=>'2',                                       'type'=>'integer', 'group'=>'security',      'label'=>'Max Proxies per Beneficiary','description'=>'Maximum number of authorized proxies allowed (RA 11310 = 2).',    'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'session_lifetime',      'value'=>'120',                                     'type'=>'integer', 'group'=>'security',      'label'=>'Session Lifetime (minutes)', 'description'=>'How long an authenticated session lasts before auto-logout.',      'created_at'=>$now,'updated_at'=>$now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
