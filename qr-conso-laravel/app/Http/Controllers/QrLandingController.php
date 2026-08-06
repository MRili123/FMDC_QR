<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Support\Signer;
use Illuminate\Http\Request;

/**
 * Page de vérification affichée après un scan (§9, lutte contre le quishing).
 *
 * Elle montre l'identité de l'établissement AVANT le formulaire : un faux code
 * collé par-dessus l'officiel ne peut pas produire cette page, faute de
 * signature valide.
 */
class QrLandingController extends Controller
{
    public function show(Request $request, string $code)
    {
        $locale = in_array($request->query('lang'), ['fr', 'ar'], true)
            ? $request->query('lang')
            : config('app.locale');

        app()->setLocale($locale);

        $qr = QrCode::where('code', $code)->first();

        // Code inconnu, désactivé, ou signature qui ne correspond pas : on
        // n'ouvre pas le parcours, on avertit.
        if (! $qr || ! $qr->active || ! Signer::safeEquals($qr->signature, Signer::hmac($qr->code))) {
            return response()->view('public.qr.invalid', [
                'locale' => $locale,
                'code' => $code,
                'known' => (bool) $qr,
            ], 404);
        }

        $qr->increment('scan_count');

        return view('public.qr.landing', [
            'locale' => $locale,
            'qr' => $qr,
        ]);
    }

    /**
     * Signalement d'un QR altéré (§9). Volontairement sans authentification :
     * la personne qui remarque l'anomalie est rarement inscrite.
     */
    public function report(Request $request, string $code)
    {
        $qr = QrCode::where('code', $code)->first();
        $qr?->increment('reported_count');

        return back()->with('status', __('flash.qrReported'));
    }
}
