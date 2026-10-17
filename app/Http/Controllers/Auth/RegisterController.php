<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\OrganizationRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __construct(private readonly OrganizationRegistrationService $service) {}

    public function showRegister(): mixed
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(RegisterRequest $request): mixed
    {
        $user = $this->service->register($request->validated());
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->expectsJson() || $request->ajax()) {
            return $this->created(['redirect' => route('dashboard')], 'Welcome — your organization is ready.');
        }

        return redirect()->route('dashboard');
    }
}
