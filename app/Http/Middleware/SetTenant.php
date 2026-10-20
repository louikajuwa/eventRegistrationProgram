<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the current tenant from the authenticated user for the rest of the request.
 * A SuperAdmin has no organization_id, so the tenant stays unset and the TenantScope
 * no-ops — letting platform staff see across all organizations.
 *
 * Also enforces organization deactivation: if the user's organization has been switched
 * off by a SuperAdmin, any existing session is terminated on the next request.
 *
 * Applied to the authenticated admin routes only; public registrant routes omit it
 * on purpose (they resolve events by slug across tenants).
 */
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated.',
                    'data' => ['redirect' => route('login')],
                    'errors' => null,
                ], 401);
            }

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
        }

        if ($user && $user->organization_id) {
            if (! $user->organization?->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your organization has been deactivated.',
                        'data' => ['redirect' => route('login')],
                        'errors' => null,
                    ], 403);
                }

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your organization has been deactivated. Please contact support.']);
            }

            TenantContext::set($user->organization_id);
        }

        return $next($request);
    }
}
