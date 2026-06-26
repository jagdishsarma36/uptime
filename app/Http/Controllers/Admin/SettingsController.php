<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $emailSettings = Setting::getGroup('email');
        $slackSettings = Setting::getGroup('slack');
        $alertSettings = Setting::getGroup('alerts');
        $generalSettings = Setting::getGroup('general');

        return view('admin.settings.index', compact(
            'emailSettings', 'slackSettings', 'alertSettings', 'generalSettings'
        ));
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'mail_MAILER' => 'required|in:smtp,sendmail,mailgun,ses,postmark,resend,log',
            'mail_HOST' => 'nullable|string|max:255',
            'mail_PORT' => 'nullable|integer|min:1|max:65535',
            'mail_USERNAME' => 'nullable|string|max:255',
            'mail_PASSWORD' => 'nullable|string|max:255',
            'mail_ENCRYPTION' => 'nullable|in:tls,ssl,null',
            'mail_FROM_ADDRESS' => 'required|email|max:255',
            'mail_FROM_NAME' => 'required|string|max:255',
            'alert_email_to' => 'required|email|max:255',
        ]);

        $map = [
            'mail_MAILER' => 'MAIL_MAILER',
            'mail_HOST' => 'MAIL_HOST',
            'mail_PORT' => 'MAIL_PORT',
            'mail_USERNAME' => 'MAIL_USERNAME',
            'mail_PASSWORD' => 'MAIL_PASSWORD',
            'mail_ENCRYPTION' => 'MAIL_ENCRYPTION',
            'mail_FROM_ADDRESS' => 'MAIL_FROM_ADDRESS',
            'mail_FROM_NAME' => 'MAIL_FROM_NAME',
            'alert_email_to' => 'ALERT_EMAIL_TO',
        ];

        foreach ($map as $inputKey => $settingKey) {
            Setting::set($settingKey, $validated[$inputKey] ?? '', 'email');
        }

        $this->syncEnv($map, $validated);

        $this->applyMailConfig();

        return back()->with('success', 'Email settings updated.');
    }

    public function updateSlack(Request $request)
    {
        $validated = $request->validate([
            'slack_webhook_url' => 'nullable|url|max:500',
            'slack_channel' => 'nullable|string|max:100',
            'slack_enabled' => 'boolean',
        ]);

        Setting::set('SLACK_WEBHOOK_URL', $validated['slack_webhook_url'] ?? '', 'slack');
        Setting::set('SLACK_CHANNEL', $validated['slack_channel'] ?? '', 'slack');
        Setting::set('SLACK_ENABLED', ($validated['slack_enabled'] ?? false) ? '1' : '0', 'slack');

        $this->syncEnv([
            'SLACK_WEBHOOK_URL' => 'SLACK_WEBHOOK_URL',
            'SLACK_CHANNEL' => 'SLACK_CHANNEL',
        ], $validated);

        return back()->with('success', 'Slack settings updated.');
    }

    public function updateAlerts(Request $request)
    {
        $validated = $request->validate([
            'alert_on_down' => 'boolean',
            'alert_on_up' => 'boolean',
            'alert_on_ssl_expiry' => 'boolean',
            'alert_on_domain_expiry' => 'boolean',
            'ssl_alert_threshold_days' => 'required|integer|min:1|max:365',
            'domain_alert_threshold_days' => 'required|integer|min:1|max:365',
            'alert_throttle_minutes' => 'required|integer|min:1|max:60',
            'default_check_interval' => 'required|integer|min:15|max:3600',
        ]);

        Setting::set('ALERT_ON_DOWN', ($validated['alert_on_down'] ?? true) ? '1' : '0', 'alerts');
        Setting::set('ALERT_ON_UP', ($validated['alert_on_up'] ?? true) ? '1' : '0', 'alerts');
        Setting::set('ALERT_ON_SSL_EXPIRY', ($validated['alert_on_ssl_expiry'] ?? true) ? '1' : '0', 'alerts');
        Setting::set('ALERT_ON_DOMAIN_EXPIRY', ($validated['alert_on_domain_expiry'] ?? true) ? '1' : '0', 'alerts');
        Setting::set('SSL_ALERT_THRESHOLD_DAYS', (string) ($validated['ssl_alert_threshold_days'] ?? 20), 'alerts');
        Setting::set('DOMAIN_ALERT_THRESHOLD_DAYS', (string) ($validated['domain_alert_threshold_days'] ?? 20), 'alerts');
        Setting::set('ALERT_THROTTLE_MINUTES', (string) ($validated['alert_throttle_minutes'] ?? 5), 'alerts');
        Setting::set('DEFAULT_CHECK_INTERVAL', (string) ($validated['default_check_interval'] ?? 300), 'alerts');

        return back()->with('success', 'Alert settings updated.');
    }

    public function testEmail(Request $request)
    {
        $this->applyMailConfig();

        $to = Setting::get('ALERT_EMAIL_TO', config('mail.from.address'));
        $mailer = config('mail.default');

        try {
            \Illuminate\Support\Facades\Mail::raw(
                "This is a test email from UptimeGuard.\n\nMailer: {$mailer}\nSent at: " . now()->toDateTimeString(),
                function ($mail) use ($to) {
                    $mail->to($to)
                        ->subject('[UptimeGuard] Test Email');
                }
            );

            return back()->with('success', "Test email sent to {$to} via {$mailer}");
        } catch (\Exception $e) {
            return back()->withErrors(['test' => 'Failed to send: ' . $e->getMessage()]);
        }
    }

    private function applyMailConfig(): void
    {
        $mailer = Setting::get('MAIL_MAILER');
        $host = Setting::get('MAIL_HOST');
        $port = Setting::get('MAIL_PORT');
        $username = Setting::get('MAIL_USERNAME');
        $password = Setting::get('MAIL_PASSWORD');
        $encryption = Setting::get('MAIL_ENCRYPTION');
        $fromAddress = Setting::get('MAIL_FROM_ADDRESS');
        $fromName = Setting::get('MAIL_FROM_NAME');

        if ($mailer) {
            config(['mail.default' => $mailer]);
        }

        if ($host || $port || $username || $password) {
            $smtpConfig = config('mail.mailers.smtp');
            config(['mail.mailers.smtp' => array_merge($smtpConfig, [
                'host' => $host ?? $smtpConfig['host'],
                'port' => $port ? (int) $port : $smtpConfig['port'],
                'username' => $username ?? $smtpConfig['username'],
                'password' => $password ?? $smtpConfig['password'],
                'scheme' => $encryption && $encryption !== 'null' ? $encryption : null,
            ])]);
        }

        if ($fromAddress || $fromName) {
            config(['mail.from' => [
                'address' => $fromAddress ?? config('mail.from.address'),
                'name' => $fromName ?? config('mail.from.name'),
            ]]);
        }
    }

    public function testSlack(Request $request)
    {
        $webhookUrl = Setting::get('SLACK_WEBHOOK_URL');

        if (!$webhookUrl) {
            return back()->withErrors(['test' => 'No Slack webhook URL configured.']);
        }

        try {
            \Illuminate\Support\Facades\Http::timeout(10)
                ->post($webhookUrl, [
                    'text' => ':white_check_mark: UptimeGuard Slack test at ' . now()->toDateTimeString(),
                ])
                ->throw();

            return back()->with('success', 'Slack test message sent successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['test' => 'Failed to send: ' . $e->getMessage()]);
        }
    }

    private function syncEnv(array $map, array $data): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($map as $inputKey => $envKey) {
            $value = $data[$inputKey] ?? '';
            $pattern = "/^{$envKey}=.*/m";
            $replacement = "{$envKey}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
