<?php

namespace App\Jobs;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public int $backoff = 30;

    public function __construct(
        public Monitor $monitor,
        public string $alertType,
        public ?string $previousStatus = null,
        public ?array $context = []
    ) {
        $this->onQueue('alerts');
    }

    public function handle(): void
    {
        $channels = $this->monitor->team->notificationChannels()
            ->where('is_active', true)
            ->get();

        if ($channels->isEmpty()) {
            Log::info("No notification channels configured", ['monitor_id' => $this->monitor->id]);
            return;
        }

        $message = $this->buildMessage();

        foreach ($channels as $channel) {
            $recentAlert = NotificationLog::where('monitor_id', $this->monitor->id)
                ->where('channel_type', $channel->type)
                ->where('sent_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($recentAlert) {
                NotificationLog::create([
                    'monitor_id' => $this->monitor->id,
                    'notification_channel_id' => $channel->id,
                    'channel_type' => $channel->type,
                    'status' => 'throttled',
                    'message' => $message,
                    'sent_at' => now(),
                ]);
                continue;
            }

            try {
                $this->sendToChannel($channel, $message);

                NotificationLog::create([
                    'monitor_id' => $this->monitor->id,
                    'notification_channel_id' => $channel->id,
                    'channel_type' => $channel->type,
                    'status' => 'sent',
                    'message' => $message,
                    'sent_at' => now(),
                ]);

            } catch (\Exception $e) {
                NotificationLog::create([
                    'monitor_id' => $this->monitor->id,
                    'notification_channel_id' => $channel->id,
                    'channel_type' => $channel->type,
                    'status' => 'failed',
                    'message' => $message,
                    'error_message' => $e->getMessage(),
                    'sent_at' => now(),
                ]);

                Log::error("Alert send failed", [
                    'monitor_id' => $this->monitor->id,
                    'channel' => $channel->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildMessage(): string
    {
        return match ($this->alertType) {
            'down' => "ALERT: {$this->monitor->name} is DOWN (was {$this->previousStatus})",
            'up' => "RECOVERED: {$this->monitor->name} is back UP (was down)",
            'ssl_expiry' => "SSL EXPIRY: {$this->context['domain']} SSL certificate expires in {$this->context['days_left']} days ({$this->context['expiry_date']})",
            'domain_expiry' => "DOMAIN EXPIRY: {$this->context['domain']} domain expires in {$this->context['days_left']} days ({$this->context['expiry_date']})",
            default => "Alert for {$this->monitor->name}",
        };
    }

    private function sendToChannel(NotificationChannel $channel, string $message): void
    {
        switch ($channel->type) {
            case 'email':
                $this->sendEmail($channel, $message);
                break;
            case 'slack':
                $this->sendSlack($channel, $message);
                break;
            case 'webhook':
                $this->sendWebhook($channel, $message);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported channel type: {$channel->type}");
        }
    }

    private function sendEmail(NotificationChannel $channel, string $message): void
    {
        $config = $channel->config;
        $to = $config['email'] ?? $config['to'] ?? null;

        if (!$to) {
            throw new \RuntimeException('No email address configured');
        }

        $this->applyMailConfig();

        \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($to) {
            $mail->to($to)
                ->subject("[UptimeGuard] {$this->monitor->name} - " . ucfirst($this->alertType));
        });
    }

    private function sendSlack(NotificationChannel $channel, string $message): void
    {
        $webhookUrl = $channel->config['webhook_url'] ?? config('slack.webhook_url');

        if (!$webhookUrl) {
            throw new \RuntimeException('No Slack webhook URL configured');
        }

        $color = match ($this->alertType) {
            'down' => '#dc3545',
            'up' => '#28a745',
            default => '#ffc107',
        };

        $payload = [
            'attachments' => [
                [
                    'color' => $color,
                    'title' => "UptimeGuard Alert",
                    'text' => $message,
                    'fields' => [
                        ['title' => 'Monitor', 'value' => $this->monitor->name, 'short' => true],
                        ['title' => 'URL', 'value' => $this->monitor->url, 'short' => true],
                        ['title' => 'Status', 'value' => ucfirst($this->alertType), 'short' => true],
                        ['title' => 'Time', 'value' => now()->toDateTimeString(), 'short' => true],
                    ],
                ],
            ],
        ];

        \Illuminate\Support\Facades\Http::timeout(10)
            ->post($webhookUrl, $payload)
            ->throw();
    }

    private function sendWebhook(NotificationChannel $channel, string $message): void
    {
        $webhookUrl = $channel->config['url'] ?? null;

        if (!$webhookUrl) {
            throw new \RuntimeException('No webhook URL configured');
        }

        $payload = [
            'event' => $this->alertType,
            'monitor' => [
                'id' => $this->monitor->id,
                'name' => $this->monitor->name,
                'url' => $this->monitor->url,
                'status' => $this->monitor->status,
            ],
            'message' => $message,
            'previous_status' => $this->previousStatus,
            'context' => $this->context,
            'timestamp' => now()->toIso8601String(),
        ];

        $headers = $channel->config['headers'] ?? [];

        \Illuminate\Support\Facades\Http::timeout(10)
            ->withHeaders(array_merge(['Content-Type' => 'application/json'], $headers))
            ->post($webhookUrl, $payload)
            ->throw();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("SendAlertJob failed permanently", [
            'monitor_id' => $this->monitor->id,
            'alert_type' => $this->alertType,
            'error' => $exception->getMessage(),
        ]);
    }

    private function applyMailConfig(): void
    {
        $mailer = \App\Models\Setting::get('MAIL_MAILER');
        $host = \App\Models\Setting::get('MAIL_HOST');
        $port = \App\Models\Setting::get('MAIL_PORT');
        $username = \App\Models\Setting::get('MAIL_USERNAME');
        $password = \App\Models\Setting::get('MAIL_PASSWORD');
        $encryption = \App\Models\Setting::get('MAIL_ENCRYPTION');
        $fromAddress = \App\Models\Setting::get('MAIL_FROM_ADDRESS');
        $fromName = \App\Models\Setting::get('MAIL_FROM_NAME');

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
}
