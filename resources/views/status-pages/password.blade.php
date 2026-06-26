<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $statusPage->title }} - Password Required</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="min-h-full bg-gray-50 flex items-center justify-center">
    <div class="max-w-sm w-full px-4">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ $statusPage->title }}</h1>
            <p class="mt-2 text-sm text-gray-600">This status page is password protected.</p>
        </div>
        <form method="POST" action="{{ route('status.verify', $statusPage->slug) }}" class="bg-white shadow rounded-lg border border-gray-200 p-6">
            @csrf
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required autofocus
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="mt-4 w-full inline-flex items-center justify-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Access Status Page
            </button>
        </form>
    </div>
</body>
</html>
