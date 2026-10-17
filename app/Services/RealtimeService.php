<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Redis;

/**
 * Bridges Laravel -> Node Socket.IO via Redis pub/sub, and issues the short-lived
 * HMAC handshake token the socket server verifies (see socket-server/index.js).
 *
 * Rooms are namespaced by organization_id so a tenant only ever receives its own
 * events. The authorized room list is embedded in the (server-signed) token, so a
 * client cannot join another tenant's room.
 */
class RealtimeService
{
    /** Publish an event to one or more rooms. Best-effort (never breaks the request). */
    public function publish(array $rooms, string $event, array $payload = []): void
    {
        if (empty($rooms)) {
            return;
        }
        try {
            Redis::connection('realtime')->publish($this->channel(), json_encode([
                'event' => $event, 'rooms' => array_values($rooms), 'payload' => $payload,
            ]));
        } catch (\Throwable) {
            // realtime is an enhancement — swallow if redis is unreachable
        }
    }

    /**
     * Build a short-lived token (rooms authorized server-side from the user's tenant).
     *
     * @return array{token:string,rooms:string[]}
     */
    public function tokenFor(User $user): array
    {
        $rooms = ['user.'.$user->id];
        if ($user->organization_id) {
            $rooms[] = 'org.'.$user->organization_id;
        }
        if ($user->isSuperAdmin()) {
            $rooms[] = 'admin';
        }

        $body = $this->b64url(json_encode(['uid' => $user->id, 'rooms' => $rooms, 'exp' => time() + 300]));
        $sig = hash_hmac('sha256', $body, $this->secret());

        return ['token' => $body.'.'.$sig, 'rooms' => $rooms];
    }

    private function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function secret(): string
    {
        return (string) config('realtime.secret', '');
    }

    private function channel(): string
    {
        return (string) config('realtime.channel', 'events.rt');
    }
}
