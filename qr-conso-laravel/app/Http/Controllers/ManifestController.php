<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * La note de cadrage retient une PWA plutôt qu'une application à télécharger
 * (§6.2) : le manifeste permet l'ajout à l'écran d'accueil sans boutique.
 */
class ManifestController extends Controller
{
    public function show(string $locale): JsonResponse
    {
        return response()->json([
            'name' => 'QR Conso Maroc — FMDC',
            'short_name' => 'QR Conso',
            'start_url' => "/{$locale}",
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f6f8fb',
            'theme_color' => '#1b4d8f',
            'lang' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            'icons' => [
                ['src' => '/icon.svg', 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any maskable'],
            ],
        ]);
    }
}
