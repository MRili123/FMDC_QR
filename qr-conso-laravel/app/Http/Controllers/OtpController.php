<?php

namespace App\Http\Controllers;

use App\Models\OtpChallenge;
use App\Support\Signer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Vérification du téléphone par OTP (§7, écran 6).
 *
 * Le pilote n'a pas de contrat opérateur : le pilote `log` écrit le code dans
 * les journaux applicatifs au lieu de l'envoyer. Brancher un vrai fournisseur
 * revient à remplacer la méthode `send`.
 */
class OtpController extends Controller
{
    public function request(Request $request, string $locale): JsonResponse
    {
        $data = $request->validate([
            'telephone' => ['required', 'string', 'max:20'],
        ]);

        $phone = preg_replace('/\s+/', '', $data['telephone']);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpChallenge::create([
            'phone' => $phone,
            'code_hash' => Signer::hmac($code),
            'expires_at' => now()->addMinutes(config('qrconso.otp.ttl_minutes')),
        ]);

        $this->send($phone, $code);

        return response()->json(['sent' => true]);
    }

    public function verify(Request $request, string $locale): JsonResponse
    {
        $data = $request->validate([
            'telephone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $phone = preg_replace('/\s+/', '', $data['telephone']);

        $challenge = OtpChallenge::where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $challenge) {
            return response()->json(['verified' => false, 'reason' => 'expired'], 422);
        }

        if ($challenge->attempts >= config('qrconso.otp.max_attempts')) {
            return response()->json(['verified' => false, 'reason' => 'too_many_attempts'], 429);
        }

        $challenge->increment('attempts');

        if (! Signer::safeEquals($challenge->code_hash, Signer::hmac($data['code']))) {
            return response()->json(['verified' => false, 'reason' => 'mismatch'], 422);
        }

        $challenge->update(['consumed_at' => now()]);

        return response()->json(['verified' => true]);
    }

    private function send(string $phone, string $code): void
    {
        if (config('qrconso.sms_driver') === 'log') {
            Log::info("[sms:stub] -> {$phone} : FMDC — votre code de vérification : {$code}");
        }
    }
}
