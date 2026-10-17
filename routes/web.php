<?php

use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventDashboardController;
use App\Http\Controllers\Admin\EventQuestionController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\RegistrationController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Public\PublicEventController;
use App\Http\Controllers\Public\PublicRegistrationController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

/*
 | Public registrant routes — NO auth, NO tenant middleware (resolved by slug).
 */
Route::get('e/{event:slug}', [PublicEventController::class, 'show'])->name('public.events.show');
Route::post('e/{event:slug}', [PublicRegistrationController::class, 'store'])
    ->middleware('throttle:20,1')->name('public.registrations.store');
Route::get('e/{event:slug}/thanks/{reference}', [PublicRegistrationController::class, 'thanks'])
    ->name('public.events.thanks');

/*
 | Guest routes
 */
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('register', [RegisterController::class, 'register'])->name('register.attempt');

    Route::get('forgot-password', [PasswordController::class, 'showForgot'])->name('password.request');
    Route::post('forgot-password', [PasswordController::class, 'sendResetLink'])->name('password.email');
    Route::get('reset-password/{token}', [PasswordController::class, 'showReset'])->name('password.reset');
    Route::post('reset-password', [PasswordController::class, 'resetPassword'])->name('password.update');
});

/*
 | Authenticated routes (tenant bound from the user's organization).
 */
Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/all', [NotificationController::class, 'all'])->name('notifications.all');
    Route::post('notifications/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('realtime/token', [RealtimeController::class, 'token'])->name('realtime.token');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [PasswordController::class, 'changePassword'])->name('profile.password');

    /*
     | Events
     */
    Route::get('events', [EventController::class, 'index'])
        ->middleware('permission:events.view')->name('events.index');
    Route::post('events', [EventController::class, 'store'])
        ->middleware('permission:events.create')->name('events.store');
    Route::put('events/{event}', [EventController::class, 'update'])
        ->middleware('permission:events.update')->name('events.update');
    Route::delete('events/{event}', [EventController::class, 'destroy'])
        ->middleware('permission:events.delete')->name('events.destroy');

    // Form builder
    Route::get('events/{event}/questions', [EventQuestionController::class, 'index'])
        ->middleware('permission:events.update')->name('events.questions.index');
    Route::put('events/{event}/questions', [EventQuestionController::class, 'sync'])
        ->middleware('permission:events.update')->name('events.questions.sync');

    // Per-event confirmation email
    Route::get('events/{event}/email', [EventController::class, 'email'])
        ->middleware('permission:events.update')->name('events.email');
    Route::put('events/{event}/email', [EventController::class, 'updateEmail'])
        ->middleware('permission:events.update')->name('events.email.update');

    // Live event dashboard
    Route::get('events/{event}/dashboard', [EventDashboardController::class, 'show'])
        ->middleware('permission:events.view')->name('events.dashboard');

    /*
     | Registrations + attendance
     */
    Route::get('registrations', [RegistrationController::class, 'index'])
        ->middleware('permission:registrations.view')->name('registrations.index');
    Route::get('registrations/{registration}', [RegistrationController::class, 'show'])
        ->middleware('permission:registrations.view')->name('registrations.show');
    Route::put('registrations/{registration}/attendance', [RegistrationController::class, 'toggleAttendance'])
        ->middleware('permission:registrations.update')->name('registrations.attendance');
    Route::put('registrations/{registration}/status', [RegistrationController::class, 'updateStatus'])
        ->middleware('permission:registrations.update')->name('registrations.status');
    Route::delete('registrations/{registration}', [RegistrationController::class, 'destroy'])
        ->middleware('permission:registrations.delete')->name('registrations.destroy');

    /*
     | Reports (Excel / CSV / PDF)
     */
    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('permission:reports.view')->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])
        ->middleware('permission:reports.view')->name('reports.export');

    /*
     | Settings (per-organization)
     */
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])
            ->middleware('permission:settings.view')->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])
            ->middleware('permission:settings.update')->name('settings.update');
        Route::post('settings/test-email', [SettingController::class, 'testEmail'])
            ->middleware('permission:settings.update')->name('settings.test-email');
    });

    /*
     | Platform operator (SuperAdmin) — read-only oversight of all organizations.
     */
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function (): void {
        Route::get('organizations', [OrganizationController::class, 'index'])->name('organizations.index');
        Route::put('organizations/{organization}/status', [OrganizationController::class, 'updateStatus'])->name('organizations.status');
        Route::get('organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
        Route::get('organizations/{organization}/events/{event}', [OrganizationController::class, 'event'])->name('organizations.event');
    });
});
