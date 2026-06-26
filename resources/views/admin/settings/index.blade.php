<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Admin Settings</h2>
    </x-slot>

    <div class="max-w-4xl space-y-8">
        {{-- Email Settings --}}
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Email Configuration</h3>
                <form method="POST" action="{{ route('admin.settings.test-email') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Send Test Email</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.settings.update-email') }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="mail_MAILER" value="Mail Driver" />
                        <select id="mail_MAILER" name="mail_MAILER" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach(['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'resend', 'log'] as $driver)
                            <option value="{{ $driver }}" {{ ($emailSettings['MAIL_MAILER'] ?? config('mail.default')) === $driver ? 'selected' : '' }}>{{ ucfirst($driver) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('mail_MAILER')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_HOST" value="SMTP Host" />
                        <x-text-input id="mail_HOST" name="mail_HOST" type="text" class="mt-1 block w-full" :value="old('mail_HOST', $emailSettings['MAIL_HOST'] ?? config('mail.mailers.smtp.host'))" placeholder="smtp.example.com" />
                        <x-input-error :messages="$errors->get('mail_HOST')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_PORT" value="SMTP Port" />
                        <x-text-input id="mail_PORT" name="mail_PORT" type="number" class="mt-1 block w-full" :value="old('mail_PORT', $emailSettings['MAIL_PORT'] ?? config('mail.mailers.smtp.port'))" placeholder="587" />
                        <x-input-error :messages="$errors->get('mail_PORT')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_ENCRYPTION" value="Encryption" />
                        <select id="mail_ENCRYPTION" name="mail_ENCRYPTION" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach(['tls', 'ssl', 'null'] as $enc)
                            <option value="{{ $enc }}" {{ ($emailSettings['MAIL_ENCRYPTION'] ?? config('mail.mailers.smtp.encryption')) === $enc ? 'selected' : '' }}>{{ strtoupper($enc) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('mail_ENCRYPTION')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_USERNAME" value="SMTP Username" />
                        <x-text-input id="mail_USERNAME" name="mail_USERNAME" type="text" class="mt-1 block w-full" :value="old('mail_USERNAME', $emailSettings['MAIL_USERNAME'] ?? config('mail.mailers.smtp.username'))" />
                        <x-input-error :messages="$errors->get('mail_USERNAME')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_PASSWORD" value="SMTP Password" />
                        <x-text-input id="mail_PASSWORD" name="mail_PASSWORD" type="password" class="mt-1 block w-full" :value="old('mail_PASSWORD', $emailSettings['MAIL_PASSWORD'] ?? config('mail.mailers.smtp.password'))" />
                        <x-input-error :messages="$errors->get('mail_PASSWORD')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_FROM_ADDRESS" value="From Address" />
                        <x-text-input id="mail_FROM_ADDRESS" name="mail_FROM_ADDRESS" type="email" class="mt-1 block w-full" :value="old('mail_FROM_ADDRESS', $emailSettings['MAIL_FROM_ADDRESS'] ?? config('mail.from.address'))" required />
                        <x-input-error :messages="$errors->get('mail_FROM_ADDRESS')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="mail_FROM_NAME" value="From Name" />
                        <x-text-input id="mail_FROM_NAME" name="mail_FROM_NAME" type="text" class="mt-1 block w-full" :value="old('mail_FROM_NAME', $emailSettings['MAIL_FROM_NAME'] ?? config('mail.from.name'))" required />
                        <x-input-error :messages="$errors->get('mail_FROM_NAME')" class="mt-1" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="alert_email_to" value="Alert Recipient Email" />
                        <x-text-input id="alert_email_to" name="alert_email_to" type="email" class="mt-1 block w-full" :value="old('alert_email_to', $emailSettings['ALERT_EMAIL_TO'] ?? config('mail.from.address'))" required />
                        <p class="mt-1 text-xs text-gray-500">Where all alert notifications will be sent.</p>
                        <x-input-error :messages="$errors->get('alert_email_to')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button>Save Email Settings</x-primary-button>
                </div>
            </form>
        </div>

        {{-- Slack Settings --}}
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Slack Configuration</h3>
                <form method="POST" action="{{ route('admin.settings.test-slack') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium">Send Test Message</button>
                </form>
            </div>
            <form method="POST" action="{{ route('admin.settings.update-slack') }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="slack_enabled" value="0">
                            <input type="checkbox" name="slack_enabled" value="1" {{ ($slackSettings['SLACK_ENABLED'] ?? '0') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <x-input-label for="slack_enabled" value="Enable Slack Notifications" class="!mb-0" />
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="slack_webhook_url" value="Webhook URL" />
                        <x-text-input id="slack_webhook_url" name="slack_webhook_url" type="url" class="mt-1 block w-full" :value="old('slack_webhook_url', $slackSettings['SLACK_WEBHOOK_URL'] ?? '')" placeholder="https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX" />
                        <p class="mt-1 text-xs text-gray-500">Create an incoming webhook in your Slack workspace settings.</p>
                        <x-input-error :messages="$errors->get('slack_webhook_url')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="slack_channel" value="Channel (optional)" />
                        <x-text-input id="slack_channel" name="slack_channel" type="text" class="mt-1 block w-full" :value="old('slack_channel', $slackSettings['SLACK_CHANNEL'] ?? '')" placeholder="#alerts" />
                        <x-input-error :messages="$errors->get('slack_channel')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button>Save Slack Settings</x-primary-button>
                </div>
            </form>
        </div>

        {{-- Alert Defaults --}}
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Default Alert Settings</h3>
                <p class="mt-1 text-sm text-gray-500">These are the default settings applied to new monitors.</p>
            </div>
            <form method="POST" action="{{ route('admin.settings.update-alerts') }}" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <h4 class="text-sm font-medium text-gray-700">Alert Triggers</h4>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="alert_on_down" value="0">
                            <input type="checkbox" name="alert_on_down" value="1" {{ ($alertSettings['ALERT_ON_DOWN'] ?? '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label class="text-sm text-gray-700">Alert when monitor goes down</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="alert_on_up" value="0">
                            <input type="checkbox" name="alert_on_up" value="1" {{ ($alertSettings['ALERT_ON_UP'] ?? '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label class="text-sm text-gray-700">Alert when monitor recovers</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="alert_on_ssl_expiry" value="0">
                            <input type="checkbox" name="alert_on_ssl_expiry" value="1" {{ ($alertSettings['ALERT_ON_SSL_EXPIRY'] ?? '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label class="text-sm text-gray-700">Alert on SSL certificate expiry</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="alert_on_domain_expiry" value="0">
                            <input type="checkbox" name="alert_on_domain_expiry" value="1" {{ ($alertSettings['ALERT_ON_DOMAIN_EXPIRY'] ?? '1') === '1' ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <label class="text-sm text-gray-700">Alert on domain expiry</label>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="ssl_alert_threshold_days" value="SSL Alert Threshold (days)" />
                            <x-text-input id="ssl_alert_threshold_days" name="ssl_alert_threshold_days" type="number" class="mt-1 block w-full" :value="old('ssl_alert_threshold_days', $alertSettings['SSL_ALERT_THRESHOLD_DAYS'] ?? 20)" min="1" max="365" />
                            <p class="mt-1 text-xs text-gray-500">Alert when SSL certificate expires within this many days.</p>
                        </div>
                        <div>
                            <x-input-label for="domain_alert_threshold_days" value="Domain Alert Threshold (days)" />
                            <x-text-input id="domain_alert_threshold_days" name="domain_alert_threshold_days" type="number" class="mt-1 block w-full" :value="old('domain_alert_threshold_days', $alertSettings['DOMAIN_ALERT_THRESHOLD_DAYS'] ?? 20)" min="1" max="365" />
                            <p class="mt-1 text-xs text-gray-500">Alert when domain expires within this many days.</p>
                        </div>
                        <div>
                            <x-input-label for="alert_throttle_minutes" value="Alert Throttle (minutes)" />
                            <x-text-input id="alert_throttle_minutes" name="alert_throttle_minutes" type="number" class="mt-1 block w-full" :value="old('alert_throttle_minutes', $alertSettings['ALERT_THROTTLE_MINUTES'] ?? 5)" min="1" max="60" />
                            <p class="mt-1 text-xs text-gray-500">Minimum time between alerts for the same monitor.</p>
                        </div>
                        <div>
                            <x-input-label for="default_check_interval" value="Default Check Interval (seconds)" />
                            <select id="default_check_interval" name="default_check_interval" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach([15 => '15s', 30 => '30s', 60 => '1 min', 120 => '2 min', 300 => '5 min', 600 => '10 min', 1800 => '30 min', 3600 => '1 hour'] as $val => $label)
                                <option value="{{ $val }}" {{ ($alertSettings['DEFAULT_CHECK_INTERVAL'] ?? 300) == $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <x-primary-button>Save Alert Settings</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
