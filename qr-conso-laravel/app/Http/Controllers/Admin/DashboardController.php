<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dossier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Le tableau de bord n'exploite que des données agrégées (§9) : aucune identité
 * de requérant n'y apparaît.
 */
class DashboardController extends Controller
{
    public function index(Request $request, string $locale)
    {
        $scope = $request->user()->visibleAssociationId();
        $base = fn () => Dossier::query()
            ->when($scope, fn ($q) => $q->where('assigned_association_id', $scope));

        $total = $base()->count();
        $resolus = $base()->whereIn('status', config('taxonomy.terminal_statuses'))->count();

        return view('admin.dashboard', [
            'locale' => $locale,
            'total' => $total,
            'ouverts' => $total - $resolus,
            'resolus' => $resolus,
            'delaiMedian' => $this->medianHandlingHours($scope),
            'parCategorie' => $this->groupCount($base(), 'categorie'),
            'parStatut' => $this->groupCount($base(), 'status'),
            'parRegion' => $this->groupCount($base(), 'region'),
            'signaux' => $this->collectiveSignals($scope),
        ]);
    }

    /** @return array<string,int> */
    private function groupCount($query, string $column): array
    {
        return $query->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->orderByDesc('total')
            ->pluck('total', $column)
            ->all();
    }

    /**
     * Délai de première prise en charge (§13). La médiane est calculée en PHP :
     * MySQL 5.7/MariaDB 10.4 n'ont pas de fonction de percentile.
     */
    private function medianHandlingHours(?string $scope): ?float
    {
        $deltas = Dossier::query()
            ->when($scope, fn ($q) => $q->where('assigned_association_id', $scope))
            ->whereNotNull('first_handled_at')
            ->get(['created_at', 'first_handled_at'])
            ->map(fn ($d) => $d->created_at->diffInMinutes($d->first_handled_at) / 60)
            ->sort()
            ->values();

        if ($deltas->isEmpty()) {
            return null;
        }

        $count = $deltas->count();
        $middle = intdiv($count, 2);

        $median = $count % 2
            ? $deltas[$middle]
            : ($deltas[$middle - 1] + $deltas[$middle]) / 2;

        return round($median, 1);
    }

    /**
     * Détection des signaux collectifs (§8) : plusieurs dossiers visant le même
     * professionnel dans la même catégorie sur 30 jours.
     *
     * @return list<array{professionnel:string,categorie:string,total:int}>
     */
    private function collectiveSignals(?string $scope): array
    {
        return Dossier::query()
            ->when($scope, fn ($q) => $q->where('assigned_association_id', $scope))
            ->whereNotNull('professionnel')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('professionnel', 'categorie', DB::raw('count(*) as total'))
            ->groupBy('professionnel', 'categorie')
            ->having('total', '>=', 2)
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'professionnel' => $row->professionnel,
                'categorie' => $row->categorie,
                'total' => (int) $row->total,
            ])
            ->all();
    }
}
