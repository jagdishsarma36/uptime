<?php

use App\Http\Controllers\BadgeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PublicStatusController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::middleware(['auth', 'verified', \App\Http\Middleware\EnsureUserHasTeam::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])->name('api.dashboard.stats');

    Route::resource('monitors', MonitorController::class)->except(['index', 'show']);
    Route::get('monitors', [MonitorController::class, 'index'])->name('monitors.index');
    Route::get('monitors/{monitor}', [MonitorController::class, 'show'])->name('monitors.show');
    Route::post('monitors/{monitor}/toggle-pause', [MonitorController::class, 'togglePause'])->name('monitors.toggle-pause');
    Route::post('monitors/{monitor}/check-now', [MonitorController::class, 'checkNow'])->name('monitors.check-now');
    Route::get('monitors/{monitor}/logs', [MonitorController::class, 'logs'])->name('monitors.logs');

    Route::resource('status-pages', StatusPageController::class)->except(['index', 'show']);
    Route::get('status-pages', [StatusPageController::class, 'index'])->name('status-pages.index');
    Route::get('status-pages/{statusPage}', [StatusPageController::class, 'show'])->name('status-pages.show');

    Route::resource('incidents', IncidentController::class)->except(['index', 'show']);
    Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::patch('incidents/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.update-status');

    Route::get('teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('teams/{teamId}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('teams/{teamId}/switch', [TeamController::class, 'switch'])->name('teams.switch');
    Route::post('teams/{teamId}/invite', [TeamController::class, 'invite'])->name('teams.invite');
    Route::delete('teams/{teamId}/members/{userId}', [TeamController::class, 'removeMember'])->name('teams.remove-member');
    Route::delete('teams/{teamId}', [TeamController::class, 'destroy'])->name('teams.destroy');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::prefix('admin')->middleware('auth')->group(function () {
        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings');
        Route::patch('settings/email', [\App\Http\Controllers\Admin\SettingsController::class, 'updateEmail'])->name('admin.settings.update-email');
        Route::patch('settings/slack', [\App\Http\Controllers\Admin\SettingsController::class, 'updateSlack'])->name('admin.settings.update-slack');
        Route::patch('settings/alerts', [\App\Http\Controllers\Admin\SettingsController::class, 'updateAlerts'])->name('admin.settings.update-alerts');
        Route::post('settings/test-email', [\App\Http\Controllers\Admin\SettingsController::class, 'testEmail'])->name('admin.settings.test-email');
        Route::post('settings/test-slack', [\App\Http\Controllers\Admin\SettingsController::class, 'testSlack'])->name('admin.settings.test-slack');
    });

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [\App\Http\Controllers\ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('status/{slug}', [PublicStatusController::class, 'show'])->name('status.public');
Route::get('status/{slug}/monitor/{monitorId}', [PublicStatusController::class, 'monitor'])->name('status.public.monitor');
Route::post('status/{slug}/verify', [PublicStatusController::class, 'verifyPassword'])->name('status.verify');

Route::get('badge/uptime/{monitor_slug}', [BadgeController::class, 'uptime'])->name('badge.uptime');
Route::get('badge/status/{monitor_slug}', [BadgeController::class, 'status'])->name('badge.status');

Route::prefix('api/v1')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::get('monitors', [\App\Http\Controllers\Api\MonitorController::class, 'index']);
    Route::post('monitors', [\App\Http\Controllers\Api\MonitorController::class, 'store']);
    Route::get('monitors/{monitor}', [\App\Http\Controllers\Api\MonitorController::class, 'show']);
    Route::put('monitors/{monitor}', [\App\Http\Controllers\Api\MonitorController::class, 'update']);
    Route::delete('monitors/{monitor}', [\App\Http\Controllers\Api\MonitorController::class, 'destroy']);
    Route::post('monitors/{monitor}/toggle-pause', [\App\Http\Controllers\Api\MonitorController::class, 'togglePause']);
    Route::post('monitors/{monitor}/check-now', [\App\Http\Controllers\Api\MonitorController::class, 'checkNow']);
    Route::get('monitors/{monitor}/logs', [\App\Http\Controllers\Api\MonitorController::class, 'logs']);
});

require __DIR__.'/auth.php';
