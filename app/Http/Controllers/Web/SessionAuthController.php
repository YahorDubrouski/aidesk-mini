<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Auth\SessionAuthRedirectQueryRequest;
use App\Http\Requests\Web\Auth\SessionLoginRequest;
use App\Http\Requests\Web\Auth\SessionRegisterRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class SessionAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function showLogin(SessionAuthRedirectQueryRequest $request): View
    {
        $validated = $request->validated();

        return view('auth.login', [
            'redirect' => $validated['redirect'] ?? null,
        ]);
    }

    public function showRegister(SessionAuthRedirectQueryRequest $request): View
    {
        $validated = $request->validated();

        return view('checkout.register', [
            'redirect' => $validated['redirect'] ?? null,
        ]);
    }

    public function login(SessionLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt(
            ['email' => $validated['email'], 'password' => $validated['password']],
            $request->boolean('remember')
        )) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectAfterAuthentication($request);
    }

    public function register(SessionRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $this->authService->register(
            $validated['name'],
            $validated['email'],
            $validated['password']
        );

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterAuthentication($request);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Explicit ?redirect= / hidden redirect only if it is a safe same-site path; otherwise home or prior intended URL.
     */
    private function redirectAfterAuthentication(Request $request): RedirectResponse
    {
        $explicit = $request->input('redirect');
        if (is_string($explicit) && $explicit !== '') {
            return redirect($explicit);
        }

        return redirect()->intended(route('home'));
    }
}
