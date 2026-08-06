<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\AssociationScope;
use App\Models\RoutingRule;
use Illuminate\Http\Request;

class AssociationController extends Controller
{
    public function index(Request $request, string $locale)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        return view('admin.associations', [
            'locale' => $locale,
            'associations' => Association::with(['scopes', 'rules'])->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request, string $locale)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'regions' => ['array'],
            'regions.*' => ['in:'.implode(',', config('taxonomy.regions'))],
            'secteurs' => ['array'],
            'secteurs.*' => ['in:'.implode(',', config('taxonomy.secteurs'))],
        ]);

        $association = Association::create([
            'nom' => $data['nom'],
            'contact' => $data['contact'] ?? null,
        ]);

        foreach ($data['regions'] ?? [] as $value) {
            AssociationScope::create([
                'association_id' => $association->id,
                'kind' => 'REGION',
                'value' => $value,
            ]);
        }
        foreach ($data['secteurs'] ?? [] as $value) {
            AssociationScope::create([
                'association_id' => $association->id,
                'kind' => 'SECTEUR',
                'value' => $value,
            ]);
        }

        return back()->with('status', __('flash.associationCreated'));
    }

    public function storeRule(Request $request, string $locale)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $data = $request->validate([
            'association_id' => ['required', 'exists:associations,id'],
            'categorie' => ['required', 'in:'.implode(',', config('taxonomy.categories'))],
            'region' => ['nullable', 'in:'.implode(',', config('taxonomy.regions'))],
            'priority' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        RoutingRule::create([
            'association_id' => $data['association_id'],
            'categorie' => $data['categorie'],
            'region' => $data['region'] ?: null,
            'priority' => $data['priority'] ?? 100,
        ]);

        return back()->with('status', __('flash.ruleCreated'));
    }

    public function destroyRule(Request $request, string $locale, RoutingRule $rule)
    {
        abort_unless($request->user()->isFmdcAdmin(), 403);

        $rule->delete();

        return back()->with('status', __('flash.ruleDeleted'));
    }
}
