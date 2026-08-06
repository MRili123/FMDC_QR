@extends('layouts.public')
@section('title', $dossier->reference)

@section('content')
<div class="container fmdc-wizard">

    <div class="fmdc-card">
        <div class="fmdc-ref mb-3">{{ $dossier->reference }}</div>

        <div class="row fmdc-no-flip">
            <div class="col-6 mb-3">
                <div class="text-muted" style="font-size:12px">{{ __('track.status') }}</div>
                <span class="fmdc-status
                    {{ in_array($dossier->status, ['RESOLU']) ? 'fmdc-status--done' : '' }}
                    {{ $dossier->status === 'CLOTURE' ? 'fmdc-status--closed' : '' }}">
                    {{ __("status.{$dossier->status}") }}
                </span>
            </div>
            <div class="col-6 mb-3">
                <div class="text-muted" style="font-size:12px">{{ __('track.opened') }}</div>
                <strong style="font-size:14px">{{ $dossier->created_at->translatedFormat('d F Y') }}</strong>
            </div>
            <div class="col-12">
                <div class="text-muted" style="font-size:12px">{{ __('track.assigned') }}</div>
                <strong style="font-size:14px">
                    {{ $dossier->assignedAssociation?->nom ?? __('track.assignedNational') }}
                </strong>
            </div>
        </div>
    </div>

    <div class="fmdc-card">
        <h2 style="font-size:16px;font-weight:600;margin-bottom:14px">{{ __('track.timeline') }}</h2>
        @if($events->isEmpty())
            <p class="text-muted mb-0">{{ __('track.noEvents') }}</p>
        @else
            <ul class="fmdc-timeline">
                @foreach($events as $event)
                    <li>
                        <time>{{ $event->created_at->translatedFormat('d F Y — H:i') }}</time>
                        @if($event->to_status)
                            <strong>{{ __("status.{$event->to_status}") }}</strong>
                        @endif
                        @if($event->note)
                            <span style="font-size:14px">{{ $event->note }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Voie de recours : le §7 impose de l'indiquer, pas seulement le statut. --}}
    <div class="fmdc-card">
        <h2 style="font-size:16px;font-weight:600;margin-bottom:8px">{{ __('track.recourse') }}</h2>
        <p class="text-muted mb-0" style="font-size:14px">{{ __('track.recourseBody') }}</p>
    </div>

</div>
@endsection
