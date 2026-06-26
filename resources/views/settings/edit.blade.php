<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Settings</h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
            </div>
            <form method="POST" action="{{ route('settings.update') }}" class="px-6 py-5 space-y-5">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', auth()->user()->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', auth()->user()->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="timezone" value="Timezone" />
                    <select id="timezone" name="timezone" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" {{ old('timezone', auth()->user()->timezone ?? 'UTC') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
            </div>
            <form method="POST" action="{{ route('settings.update') }}" class="px-6 py-5 space-y-5">
                @csrf
                @method('PATCH')
                <div>
                    <x-input-label for="current_password" value="Current Password" />
                    <x-text-input id="current_password" name="current_password" type="password" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('current_password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password" value="New Password" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Confirm New Password" />
                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Update Password</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
