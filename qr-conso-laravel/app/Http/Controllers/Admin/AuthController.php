<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(string $locale)
    {
        if (Auth::check()) {
            return redirect()->route('admin.dossiers.index', $locale);
        }

        return view('admin.login', ['locale' => $locale]);
    }

    public function login(Request $request, string $locale)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$data, 'active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('admin.login.invalid'),
            ]);
        }

        $request->session()->regenerate();

        AuditLog::record('login', 'user', (string) Auth::id(), (string) Auth::id(), Auth::user()->name);

        return redirect()->intended(route('admin.dossiers.index', $locale));
    }

    public function logout(Request $request, string $locale)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login', $locale);
    }
}
