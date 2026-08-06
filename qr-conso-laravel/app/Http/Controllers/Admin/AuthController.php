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

    /**
     * La déconnexion reste en POST : une déconnexion accessible en GET peut être
     * déclenchée par n'importe quel lien ou balise image d'une page tierce, ce
     * qui permet de faire sauter la session d'un agent à son insu.
     *
     * Mais arriver sur l'URL en la tapant ne doit pas produire une erreur 405 :
     * on affiche une confirmation, dont le bouton fait le vrai POST.
     */
    public function confirmLogout(string $locale)
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login', $locale);
        }

        return view('admin.logout', ['locale' => $locale]);
    }
}
