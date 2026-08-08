<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AuditLog;
use App\Models\Dossier;
use App\Services\DossierService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DossierController extends Controller
{
    public function __construct(
        private DossierService $dossiers,
        private OllamaService $ai,
    ) {}

    public function index(Request $request, string $locale)
    {
        $user = $request->user();

        $query = Dossier::query()
            ->with('assignedAssociation')
            ->latest();

        // Un agent ne voit que le périmètre de son association.
        if ($scope = $user->visibleAssociationId()) {
            $query->where('assigned_association_id', $scope);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($categorie = $request->query('categorie')) {
            $query->where('categorie', $categorie);
        }
        if ($q = trim((string) $request->query('q'))) {
            // Les collations MySQL sont insensibles à la casse : rien à préciser.
            $query->where('reference', 'like', "%{$q}%");
        }

        return view('admin.dossiers.index', [
            'locale' => $locale,
            'dossiers' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('status', 'categorie', 'q'),
        ]);
    }

    public function show(Request $request, string $locale, Dossier $dossier)
    {
        $this->authorizeScope($request, $dossier);

        AuditLog::record('view', 'dossier', $dossier->id, (string) $request->user()->id, $request->user()->name);

        return view('admin.dossiers.show', [
            'locale' => $locale,
            'dossier' => $dossier->load(['requerant', 'attachments', 'assignedAssociation', 'qrCode']),
            'events' => $dossier->events()->orderByDesc('created_at')->get(),
            'associations' => Association::where('active', true)->orderBy('nom')->get(),
            'aiAvailable' => $this->ai->isAvailable(),
            'extractionAvailable' => app(\App\Services\ExtractionService::class)->isEnabled(),
            // Compétence d'abord, proximité ensuite : voir RoutingService.
            'suggestions' => app(\App\Services\RoutingService::class)
                ->suggest($dossier->categorie, $dossier->region),
        ]);
    }

    public function updateStatus(Request $request, string $locale, Dossier $dossier)
    {
        $this->authorizeScope($request, $dossier);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', config('taxonomy.statuses'))],
            'note' => ['nullable', 'string', 'max:2000'],
            'public_note' => ['nullable', 'boolean'],
        ]);

        $this->dossiers->transition(
            $dossier,
            $data['status'],
            $data['note'] ?? null,
            $request->boolean('public_note'),
            (string) $request->user()->id,
            $request->user()->name,
        );

        return back()->with('status', __('flash.statusUpdated'));
    }

    public function assign(Request $request, string $locale, Dossier $dossier)
    {
        $this->authorizeScope($request, $dossier);

        $data = $request->validate([
            'association_id' => ['nullable', 'exists:associations,id'],
        ]);

        $dossier->update(['assigned_association_id' => $data['association_id'] ?: null]);

        AuditLog::record('assign', 'dossier', $dossier->id, (string) $request->user()->id, $request->user()->name);

        return back()->with('status', __('flash.assigned'));
    }

    /**
     * Suggestion IA sur un dossier déjà déposé : utile quand le récit est long
     * ou mal structuré. L'agent reste seul décideur (§8).
     */
    public function aiSuggest(Request $request, string $locale, Dossier $dossier): JsonResponse
    {
        $this->authorizeScope($request, $dossier);

        if (! $dossier->description) {
            return response()->json(['error' => 'no_description'], 422);
        }

        return response()->json($this->ai->analyse(
            $dossier->description,
            $locale,
            $dossier->resultat_attendu,
            $dossier->attachments->pluck('kind')->unique()->values()->all(),
        ));
    }

    private function authorizeScope(Request $request, Dossier $dossier): void
    {
        $scope = $request->user()->visibleAssociationId();

        abort_if($scope !== null && $dossier->assigned_association_id !== $scope, 403);
    }
}
