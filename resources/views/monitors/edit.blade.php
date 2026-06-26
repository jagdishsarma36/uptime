<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Edit Monitor</h2>
            <a href="{{ route('monitors.show', $monitor) }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Monitor</a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <form method="POST" action="{{ route('monitors.update', $monitor) }}" class="bg-white shadow rounded-lg border border-gray-200">
            @csrf
            @method('PUT')
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Monitor Details</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $monitor->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="url" value="URL" />
                    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $monitor->url)" required placeholder="https://example.com" />
                    <x-input-error :messages="$errors->get('url')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach(['http', 'https', 'tcp', 'dns', 'keyword'] as $type)
                            <option value="{{ $type }}" {{ old('type', $monitor->type) === $type ? 'selected' : '' }}>{{ strtoupper($type) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="method" value="Method" />
                        <select id="method" name="method" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach(['GET', 'POST', 'PUT', 'HEAD'] as $method)
                            <option value="{{ $method }}" {{ old('method', $monitor->method) === $method ? 'selected' : '' }}>{{ $method }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('method')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="group_id" value="Group" />
                        <select id="group_id" name="group_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">No Group</option>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id', $monitor->group_id) == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('group_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="check_interval_seconds" value="Check Interval (seconds)" />
                        <select id="check_interval_seconds" name="check_interval_seconds" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                            @foreach([15, 30, 60, 120, 300, 600, 1800, 3600] as $interval)
                            <option value="{{ $interval }}" {{ old('check_interval_seconds', $monitor->check_interval_seconds) == $interval ? 'selected' : '' }}>{{ $interval }}s</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('check_interval_seconds')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="expected_status_code" value="Expected Status Code" />
                        <x-text-input id="expected_status_code" name="expected_status_code" type="number" class="mt-1 block w-full" :value="old('expected_status_code', $monitor->expected_status_code)" />
                        <x-input-error :messages="$errors->get('expected_status_code')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="expected_keyword" value="Expected Keyword" />
                        <x-text-input id="expected_keyword" name="expected_keyword" type="text" class="mt-1 block w-full" :value="old('expected_keyword', $monitor->expected_keyword)" placeholder="Optional" />
                        <x-input-error :messages="$errors->get('expected_keyword')" class="mt-1" />
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 border-t border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900 mb-4">SSL & Domain Monitoring</h3>
                <div class="space-y-3">
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="ssl_enabled" value="1" {{ old('ssl_enabled', $monitor->ssl_enabled) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Enable SSL Certificate Monitoring</span>
                    </label>
                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="domain_enabled" value="1" {{ old('domain_enabled', $monitor->domain_enabled) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Enable Domain Expiry Monitoring</span>
                    </label>
                </div>
            </div>

            <div class="px-6 py-5 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Alert Settings</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="alert_on_down" value="1" {{ old('alert_on_down', $monitor->alert_on_down) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Alert on Down</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="alert_on_up" value="1" {{ old('alert_on_up', $monitor->alert_on_up) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Alert on Recovery</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="alert_on_ssl_expiry" value="1" {{ old('alert_on_ssl_expiry', $monitor->alert_on_ssl_expiry) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Alert on SSL Expiry</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="alert_on_domain_expiry" value="1" {{ old('alert_on_domain_expiry', $monitor->alert_on_domain_expiry) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Alert on Domain Expiry</span>
                        </label>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="ssl_alert_threshold_days" value="SSL Alert Threshold (days)" />
                            <x-text-input id="ssl_alert_threshold_days" name="ssl_alert_threshold_days" type="number" class="mt-1 block w-full" :value="old('ssl_alert_threshold_days', $monitor->ssl_alert_threshold_days)" />
                            <x-input-error :messages="$errors->get('ssl_alert_threshold_days')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="domain_alert_threshold_days" value="Domain Alert Threshold (days)" />
                            <x-text-input id="domain_alert_threshold_days" name="domain_alert_threshold_days" type="number" class="mt-1 block w-full" :value="old('domain_alert_threshold_days', $monitor->domain_alert_threshold_days)" />
                            <x-input-error :messages="$errors->get('domain_alert_threshold_days')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('monitors.show', $monitor) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
                <x-primary-button>Save Changes</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
