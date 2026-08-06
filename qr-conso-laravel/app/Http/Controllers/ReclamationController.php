<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\OtpChallenge;
use App\Models\QrCode;
use App\Services\DossierService;
use App\Services\DraftService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Les sept écrans du §7. Objectif affiché : moins de 90 secondes pour un
 * signalement simple, cinq informations obligatoires au maximum.
 */
class ReclamationController extends Controller
{
    public function __construct(
        private DraftService $draft,
        private DossierService $dossiers,
        private OllamaService $ai,
    ) {}

    /** Entrée du parcours. Un `qr` en paramètre pré-remplit l'établissement. */
    public function start(Request $request, string $locale)
    {
        $this->draft->forget();

        if ($code = $request->query('qr')) {
            $qr = QrCode::where('code', $code)->where('active', true)->first();
            if ($qr) {
                $this->draft->merge([
                    'qr_code_id' => $qr->id,
                    'qr_libelle' => $qr->libelle,
                    'etablissement' => $qr->etablissement,
                    'region' => $qr->region,
                    'secteur' => $qr->secteur,
                ]);
            }
        }

        // Une démarche peut être imposée depuis l'accueil : « Signaler une
        // pratique » doit mener directement au parcours anonyme.
        if (in_array($request->query('demarche'), config('taxonomy.demarches'), true)) {
            $this->draft->merge(['demarche' => $request->query('demarche')]);

            return redirect()->route('reclamation.categorie', $locale);
        }

        return redirect()->route('reclamation.demarche', $locale);
    }

    // ---- Écran 1 : que souhaitez-vous faire ? ----

    /**
     * Le choix entre conseil, signalement et réclamation ouvre le parcours au
     * lieu de le clore.
     *
     * Il était auparavant posé au dernier écran, ce qui obligeait une personne
     * craignant des représailles à remplir cinq écrans avant de découvrir
     * qu'elle pouvait rester anonyme. Celle qui a le plus besoin de l'anonymat
     * est justement celle qui abandonnera le formulaire avant d'y arriver.
     */
    public function demarche(string $locale)
    {
        return view('public.reclamation.demarche', [
            'locale' => $locale,
            'step' => 1,
            'draft' => $this->draft->all(),
        ]);
    }

    public function storeDemarche(Request $request, string $locale)
    {
        $data = $request->validate([
            'demarche' => ['required', 'in:'.implode(',', config('taxonomy.demarches'))],
        ]);

        $this->draft->merge($data);

        return redirect()->route('reclamation.categorie', $locale);
    }

    // ---- Écran 2 : quel problème ? ----

    public function categorie(string $locale)
    {
        if ($this->draft->firstMissingStep() === 'demarche') {
            return redirect()->route('reclamation.demarche', $locale);
        }

        return view('public.reclamation.categorie', [
            'locale' => $locale,
            'step' => 2,
            'draft' => $this->draft->all(),
        ]);
    }

    public function storeCategorie(Request $request, string $locale)
    {
        $data = $request->validate([
            'categorie' => ['required', 'string', 'in:'.implode(',', config('taxonomy.categories'))],
        ]);

        $this->draft->merge($data);

        return redirect()->route('reclamation.motif', $locale);
    }

    // ---- Écran 3 : que s'est-il passé ? ----

    public function motif(string $locale)
    {
        if ($missing = $this->draft->firstMissingStep()) {
            if ($missing === 'categorie') {
                return redirect()->route('reclamation.categorie', $locale);
            }
        }

        return view('public.reclamation.motif', [
            'locale' => $locale,
            'step' => 3,
            'draft' => $this->draft->all(),
        ]);
    }

    public function storeMotif(Request $request, string $locale)
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'in:'.implode(',', config('taxonomy.motifs'))],
        ]);

        $this->draft->merge($data);

        return redirect()->route('reclamation.decrire', $locale);
    }

    // ---- Écran 4 : décrire ----

    public function decrire(string $locale)
    {
        if ($this->draft->firstMissingStep()) {
            return redirect()->route('reclamation.'.$this->draft->firstMissingStep(), $locale);
        }

        return view('public.reclamation.decrire', [
            'locale' => $locale,
            'step' => 4,
            'draft' => $this->draft->all(),
            'aiAvailable' => $this->ai->isAvailable(),
        ]);
    }

    public function storeDecrire(Request $request, string $locale)
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
            'professionnel' => ['nullable', 'string', 'max:200'],
        ]);

        $this->draft->merge($data);

        return redirect()->route('reclamation.preuves', $locale);
    }

    /**
     * Assistance IA du §8, appelée en arrière-plan depuis l'écran « Décrire ».
     * Le consommateur voit la proposition et reste libre de l'ignorer : rien
     * n'est appliqué automatiquement à son texte.
     */
    public function assistance(Request $request, string $locale): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $draft = $this->draft->all();

        $piecesFournies = Attachment::where('draft_id', $this->draft->draftId())
            ->pluck('kind')->unique()->values()->all();

        return response()->json($this->ai->analyse(
            $data['description'],
            $locale,
            $draft['resultat_attendu'] ?? null,
            $piecesFournies,
        ));
    }

    // ---- Écran 5 : preuves ----

    public function preuves(string $locale)
    {
        if ($this->draft->firstMissingStep()) {
            return redirect()->route('reclamation.'.$this->draft->firstMissingStep(), $locale);
        }

        return view('public.reclamation.preuves', [
            'locale' => $locale,
            'step' => 5,
            'draft' => $this->draft->all(),
            'attachments' => Attachment::where('draft_id', $this->draft->draftId())->latest()->get(),
        ]);
    }

    public function storePreuves(string $locale)
    {
        return redirect()->route('reclamation.resultat', $locale);
    }

    // ---- Écran 6 : résultat attendu ----

    public function resultat(string $locale)
    {
        if ($this->draft->firstMissingStep()) {
            return redirect()->route('reclamation.'.$this->draft->firstMissingStep(), $locale);
        }

        return view('public.reclamation.resultat', [
            'locale' => $locale,
            'step' => 6,
            'draft' => $this->draft->all(),
        ]);
    }

    public function storeResultat(Request $request, string $locale)
    {
        $data = $request->validate([
            'resultat_attendu' => ['nullable', 'string', 'in:'.implode(',', config('taxonomy.resultats'))],
        ]);

        $this->draft->merge($data);

        return redirect()->route('reclamation.contact', $locale);
    }

    // ---- Écran 7 : contact minimal ----

    public function contact(string $locale)
    {
        if ($this->draft->firstMissingStep()) {
            return redirect()->route('reclamation.'.$this->draft->firstMissingStep(), $locale);
        }

        return view('public.reclamation.contact', [
            'locale' => $locale,
            'step' => 7,
            'draft' => $this->draft->all(),
        ]);
    }

    /**
     * Soumission finale. Une démarche anonyme est acceptée : le §7 distingue le
     * signalement, qui n'exige aucune identité, de la réclamation formelle.
     */
    public function submit(Request $request, string $locale)
    {
        $data = $request->validate([
            'anonyme' => ['nullable', 'boolean'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:200'],
            'nom' => ['nullable', 'string', 'max:150'],
        ]);

        $draft = $this->draft->all();

        // La démarche est choisie au premier écran : la relire ici, plutôt que
        // de la recevoir du formulaire, évite qu'un signalement annoncé anonyme
        // ne se transforme en réclamation nominative au moment de l'envoi.
        if ($missing = $this->draft->firstMissingStep()) {
            return redirect()->route('reclamation.'.$missing, $locale);
        }

        $anonyme = (bool) ($data['anonyme'] ?? false);
        $telephone = $anonyme ? null : preg_replace('/\s+/', '', $data['telephone'] ?? '');

        // Une vérification OTP n'est retenue que si elle est fraîche.
        $phoneVerified = false;
        if ($telephone) {
            $phoneVerified = OtpChallenge::where('phone', $telephone)
                ->where('consumed_at', '>=', now()->subMinutes(config('qrconso.otp.validity_minutes')))
                ->exists();
        }

        $result = $this->dossiers->create([
            'demarche' => $draft['demarche'],
            'categorie' => $draft['categorie'],
            'motif' => $draft['motif'],
            'description' => $draft['description'] ?? null,
            'resultat_attendu' => $draft['resultat_attendu'] ?? null,
            'professionnel' => $draft['professionnel'] ?? null,
            'etablissement' => $draft['etablissement'] ?? null,
            'region' => $draft['region'] ?? null,
            'qr_code_id' => $draft['qr_code_id'] ?? null,
            'draft_id' => $this->draft->draftId(),
            'contact' => $anonyme ? null : [
                'telephone' => $telephone ?: null,
                'email' => $data['email'] ?? null,
                'nom' => $data['nom'] ?? null,
                'phone_verified' => $phoneVerified,
            ],
        ]);

        $this->draft->forget();

        // La référence et le jeton transitent par la session flash : ils ne
        // doivent pas rester dans l'URL, qui finit dans l'historique du
        // téléphone et parfois dans un presse-papier partagé.
        return redirect()->route('reclamation.confirmation', $locale)->with([
            'reference' => $result['dossier']->reference,
            'token' => $result['token'],
        ]);
    }

    public function confirmation(string $locale)
    {
        $reference = session('reference');
        if (! $reference) {
            return redirect()->route('home', $locale);
        }

        return view('public.reclamation.confirmation', [
            'locale' => $locale,
            'reference' => $reference,
            'token' => session('token'),
        ]);
    }
}
