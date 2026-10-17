<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\RealtimeService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Super Admin implicitly has every permission/ability.
        Gate::before(function ($user, string $ability) {
            return $user->hasRole(RoleName::SuperAdmin->value) ? true : null;
        });

        // NOTE: mail config is applied per-organization at send time (MailConfigService::
        // applyForOrganization), because different tenants have different SMTP creds and a
        // queue worker can't know the tenant at boot.

        // Activity trail: login / logout.
        Event::listen(Login::class, function ($e): void {
            ActivityLog::create([
                'user_id' => $e->user->getKey(), 'event' => 'login',
                'ip_address' => request()->ip(), 'user_agent' => substr((string) request()->userAgent(), 0, 255),
            ]);
        });
        Event::listen(Logout::class, function ($e): void {
            if ($e->user) {
                ActivityLog::create([
                    'user_id' => $e->user->getKey(), 'event' => 'logout',
                    'ip_address' => request()->ip(), 'user_agent' => substr((string) request()->userAgent(), 0, 255),
                ]);
            }
        });

        // Push a live "notification" event to the recipient's socket room when a
        // database notification is stored (fires once per notification).
        Event::listen(
            NotificationSent::class,
            function (NotificationSent $e): void {
                if ($e->channel !== 'database' || ! $e->notifiable instanceof User) {
                    return;
                }
                $data = method_exists($e->notification, 'toArray') ? $e->notification->toArray($e->notifiable) : [];
                app(RealtimeService::class)->publish(
                    ['user.'.$e->notifiable->getKey()],
                    'notification',
                    ['title' => $data['title'] ?? 'Notification', 'message' => $data['message'] ?? ''],
                );
            }
        );
    }
}
