<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Support\Signer;
use Illuminate\Http\Request;

/**
 * Suivi transparent jusqu'à la résolution (§5, principe 4).
 *
 * L'accès exige la référence ET le jeton remis à la soumission : connaître la
 * seule référence, qui est séquentielle et donc devinable, ne doit pas suffire
 * à lire la réclamation de quelqu'un d'autre.
 */
class SuiviController extends Controller
{
    public function form(string $locale)
    {
        return view('public.suivi.form', ['locale' => $locale]);
    }

    public function lookup(Request $request, string $locale)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:30'],
            'token' => ['required', 'string', 'max:100'],
        ]);

        return redirect()->route('suivi.show', [
            'locale' => $locale,
            'reference' => trim($data['reference']),
            't' => $data['token'],
        ]);
    }

    public function show(Request $request, string $locale, string $reference)
    {
        $token = (string) $request->query('t', '');
        $dossier = Dossier::where('reference', $reference)->first();

        if (! $dossier || $token === '' || ! Signer::safeEquals($dossier->tracking_token_hash, Signer::hmac($token))) {
            return view('public.suivi.form', [
                'locale' => $locale,
                'error' => __('track.notFound'),
                'reference' => $reference,
            ]);
        }

        return view('public.suivi.show', [
            'locale' => $locale,
            'dossier' => $dossier->load('assignedAssociation'),
            // Le consommateur ne voit que les notes marquées publiques.
            'events' => $dossier->events()->where('public_note', true)->orderByDesc('created_at')->get(),
        ]);
    }
}
