<?php

namespace App\Http\Controllers;

use App\Services\ConseilService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Le conseil du §7 : « le consommateur veut connaître ses droits sans ouvrir de
 * litige ; réponse pédagogique appuyée sur le Guide et les fiches juridiques de
 * la FMDC ».
 *
 * Cette démarche ne crée aucun dossier et ne demande aucune identité : poser une
 * question sur ses droits ne doit pas coûter une inscription.
 */
class ConseilController extends Controller
{
    public function __construct(private ConseilService $conseil) {}

    public function index(string $locale)
    {
        return view('public.conseil', [
            'locale' => $locale,
            'disponible' => $this->conseil->isAvailable(),
            'suggestions' => collect(config('conseil.fiches'))
                ->take(4)
                ->map(fn ($f) => $f['titre'])
                ->all(),
        ]);
    }

    public function repondre(Request $request, string $locale): StreamedResponse
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'min:5', 'max:'.config('conseil.max_question')],
        ]);

        $fiches = $this->conseil->fichesPertinentes($data['question']);

        // Hors corpus : réponse fixe, sans passer par le modèle. Laisser un
        // modèle local improviser sur le droit marocain devant un consommateur
        // est le seul vrai risque de cette fonctionnalité.
        if ($fiches === []) {
            return response()->stream(function () {
                echo __('conseil.outOfScope');
                flush();
            }, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $flux = $this->conseil->repondreEnFlux($data['question'], $fiches, $locale);

        return response()->stream(function () use ($flux, $fiches) {
            foreach ($flux as $fragment) {
                echo $fragment;
                // Sans vidage explicite, PHP garderait tout jusqu'à la fin et le
                // flux n'aurait plus aucun intérêt.
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

            // Les sources closent la réponse : le consommateur doit pouvoir
            // vérifier d'où vient ce qu'on lui affirme.
            $sources = collect($fiches)->pluck('source')->unique()->implode(' · ');
            if ($sources !== '') {
                echo "\n\n\u{2014} ".$sources;
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
