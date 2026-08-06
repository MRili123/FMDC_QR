<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Support\Signer;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrRenderer;

/**
 * Génération des trois types de QR du §6.1. Le code est opaque et signé : il ne
 * transporte qu'un identifiant technique, jamais de donnée personnelle.
 */
class QrCodeController extends Controller
{
    public function index(Request $request, string $locale)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        return view('admin.qrcodes', [
            'locale' => $locale,
            'codes' => QrCode::latest()->get(),
        ]);
    }

    public function store(Request $request, string $locale)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $data = $request->validate([
            'type' => ['required', 'in:NATIONAL,SECTORIEL,ETABLISSEMENT'],
            'libelle' => ['required', 'string', 'max:255'],
            'secteur' => ['nullable', 'in:'.implode(',', config('taxonomy.secteurs'))],
            'region' => ['nullable', 'in:'.implode(',', config('taxonomy.regions'))],
            'etablissement' => ['nullable', 'string', 'max:255'],
            'support' => ['nullable', 'string', 'max:255'],
        ]);

        // Un slug par affiche : cela permet de repérer précisément quel support
        // a été altéré si un faux code est signalé.
        do {
            $code = Signer::randomSlug();
        } while (QrCode::where('code', $code)->exists());

        QrCode::create([
            ...$data,
            'code' => $code,
            'signature' => Signer::hmac($code),
        ]);

        return back()->with('status', __('flash.qrCreated'));
    }

    public function toggle(Request $request, string $locale, QrCode $qrCode)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $qrCode->update(['active' => ! $qrCode->active]);

        return back()->with('status', __('flash.qrToggled'));
    }

    /**
     * Affiche imprimable. L'adresse est écrite en clair sous le code : c'est la
     * première protection recommandée contre le quishing (§9).
     */
    public function poster(Request $request, string $locale, QrCode $qrCode)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $url = rtrim(config('qrconso.base_url'), '/').'/r/'.$qrCode->code;

        $svg = QrRenderer::format('svg')
            ->size(320)
            ->margin(1)
            ->errorCorrection('H')
            ->generate($url);

        return view('admin.qr-poster', [
            'locale' => $locale,
            'qr' => $qrCode,
            'svg' => $svg,
            'url' => $url,
            'printedHost' => config('qrconso.public_host').'/r/'.$qrCode->code,
        ]);
    }
}
