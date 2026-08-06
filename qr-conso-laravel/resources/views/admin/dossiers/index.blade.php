@extends('layouts.admin')
@section('title', __('admin.queue.title'))

@section('content')
<h1 class="fmdc-page-title">{{ __('admin.queue.title') }}</h1>

<form method="GET" class="fmdc-stat mb-3">
    <div class="row fmdc-no-flip">
        <div class="col-md-4 mb-2">
            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">{{ __('admin.queue.filterStatus') }}</option>
                @foreach(config('taxonomy.statuses') as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                        {{ __("status.$status") }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <select name="categorie" class="form-control" onchange="this.form.submit()">
                <option value="">{{ __('admin.queue.filterCategory') }}</option>
                @foreach(config('taxonomy.categories') as $categorie)
                    <option value="{{ $categorie }}" @selected(($filters['categorie'] ?? '') === $categorie)>
                        {{ __("categorie.$categorie") }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <input type="search" name="q" class="form-control"
                   placeholder="{{ __('admin.queue.search') }}" value="{{ $filters['q'] ?? '' }}">
        </div>
    </div>
</form>

<div class="fmdc-table table-responsive">
    <table class="table fmdc-table mb-0">
        <thead>
            <tr>
                <th>{{ __('admin.queue.reference') }}</th>
                <th>{{ __('admin.queue.category') }}</th>
                <th>{{ __('admin.queue.status') }}</th>
                <th>{{ __('admin.queue.assigned') }}</th>
                <th>{{ __('admin.queue.created') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dossiers as $dossier)
                <tr>
                    <td>
                        <a href="{{ route('admin.dossiers.show', [$locale, $dossier]) }}" class="fmdc-code">
                            {{ $dossier->reference }}
                        </a>
                    </td>
                    <td>{{ __("categorie.{$dossier->categorie}") }}</td>
                    <td>
                        <span class="fmdc-status
                            {{ $dossier->status === 'RESOLU' ? 'fmdc-status--done' : '' }}
                            {{ $dossier->status === 'CLOTURE' ? 'fmdc-status--closed' : '' }}">
                            {{ __("status.{$dossier->status}") }}
                        </span>
                    </td>
                    <td>{{ $dossier->assignedAssociation?->nom ?? __('admin.queue.unassigned') }}</td>
                    <td>{{ $dossier->created_at->translatedFormat('d M Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">{{ __('admin.queue.empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $dossiers->links() }}</div>
@endsection
