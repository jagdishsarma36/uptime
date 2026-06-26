<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!$this->app->runningInConsole() || $this->app->runningArtisan()) {
            $this->applyMailSettings();
        }
    }

    private function applyMailSettings(): void
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
                'scheme' => $encryption !== 'null' ? $encryption : null,
            ])]);
        }

        if ($fromAddress || $fromName) {
            config(['mail.from' => [
                'address' => $fromAddress ?? config('mail.from.address'),
                'name' => $fromName ?? config('mail.from.name'),
            ]]);
        }
    }
}
