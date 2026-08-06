@extends('layouts.admin')
@section('title', __('admin.dashboard.title'))

@section('content')
<h1 class="fmdc-page-title">{{ __('admin.dashboard.title') }}</h1>

<div class="row">
    @foreach([
        ['admin.dashboard.total', $total, null],
        ['admin.dashboard.open', $ouverts, null],
        ['admin.dashboard.resolved', $resolus, null],
        ['admin.dashboard.medianFirstHandling', $delaiMedian, 'admin.dashboard.hours'],
    ] as [$label, $value, $unit])
        <div class="col-md-3 col-6 mb-3">
            <div class="fmdc-stat">
                <div class="fmdc-stat__label">{{ __($label) }}</div>
                <div class="fmdc-stat__value">
                    @if($unit)
                        {{ $value === null ? '—' : __($unit, ['value' => $value]) }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    @foreach([
        ['admin.dashboard.byCategory', $parCategorie, 'categorie'],
        ['admin.dashboard.byStatus', $parStatut, 'status'],
        ['admin.dashboard.byRegion', $parRegion, 'region'],
    ] as [$title, $data, $prefix])
        <div class="col-lg-4 mb-3">
            <div class="fmdc-stat h-100">
                <h2 style="font-size:15px;font-weight:600;margin-bottom:14px">{{ __($title) }}</h2>
                @forelse($data as $key => $count)
                    @php($max = max($data ?: [1]))
                    <div class="mb-2">
                        <div class="d-flex justify-content-between" style="font-size:13px">
                            <span>{{ $key ? __("$prefix.$key") : '—' }}</span>
                            <strong>{{ $count }}</strong>
                        </div>
                        <div class="fmdc-bar-track mt-1">
                            <div class="fmdc-bar" style="width: {{ $max ? round($count / $max * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">{{ __('admin.dashboard.noData') }}</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

{{-- Détection des signaux collectifs (§8) : c'est ce qui transforme une pile de
     réclamations en veille de marché exploitable par le plaidoyer. --}}
<div class="fmdc-stat">
    <h2 style="font-size:15px;font-weight:600;margin-bottom:4px">{{ __('admin.dashboard.duplicates') }}</h2>
    <p class="text-muted" style="font-size:13px">{{ __('admin.dashboard.duplicatesHint') }}</p>

    @forelse($signaux as $signal)
        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
            <span>
                <strong>{{ $signal['professionnel'] }}</strong>
                <small class="text-muted d-block">{{ __("categorie.{$signal['categorie']}") }}</small>
            </span>
            <span class="fmdc-status">{{ __('admin.dashboard.occurrences', ['count' => $signal['total']]) }}</span>
        </div>
    @empty
        <p class="text-muted mb-0">{{ __('admin.dashboard.duplicatesNone') }}</p>
    @endforelse
</div>
@endsection
