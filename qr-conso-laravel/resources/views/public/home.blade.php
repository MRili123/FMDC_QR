@extends('layouts.public')
@section('title', __('home.title'))

@section('content')
<div class="container">

    <div class="text-center mb-4">
        <h1 style="font-size:26px;font-weight:700;margin-bottom:10px">{{ __('home.title') }}</h1>
        <p class="text-muted" style="font-size:16px">{{ __('home.subtitle') }}</p>
    </div>

    <div class="fmdc-card">
        <a href="{{ route('reclamation.start', $locale) }}" class="fmdc-btn fmdc-btn--block mb-3">
            <i class="las la-edit"></i> {{ __('home.cta') }}
        </a>

        <a href="{{ route('suivi.form', $locale) }}" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block mb-3">
            <i class="las la-search"></i> {{ __('home.ctaTrack') }}
        </a>

        {{-- Le conseil n'ouvre pas de dossier : il mérite son propre accès,
             pour qui a seulement une question à poser. --}}
        <a href="{{ route('conseil', $locale) }}" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
            <i class="las la-comments"></i> {{ __('conseil.title') }}
        </a>
    </div>

    <div class="fmdc-card">
        <div class="row text-center fmdc-no-flip">
            <div class="col-4">
                <i class="las la-bolt" style="font-size:26px;color:var(--fmdc-primary)"></i>
                <p class="mb-0 mt-2" style="font-size:13px">{{ __('home.reassure1') }}</p>
            </div>
            <div class="col-4">
                <i class="las la-user-shield" style="font-size:26px;color:var(--fmdc-primary)"></i>
                <p class="mb-0 mt-2" style="font-size:13px">{{ __('home.reassure2') }}</p>
            </div>
            <div class="col-4">
                <i class="las la-comments" style="font-size:26px;color:var(--fmdc-primary)"></i>
                <p class="mb-0 mt-2" style="font-size:13px">{{ __('home.reassure3') }}</p>
            </div>
        </div>
    </div>

    <div class="fmdc-card">
        <h2 style="font-size:17px;font-weight:600;margin-bottom:14px">{{ __('home.howTitle') }}</h2>
        <ol style="padding-inline-start:20px;margin:0;line-height:1.9">
            <li>{{ __('home.how1') }}</li>
            <li>{{ __('home.how2') }}</li>
            <li>{{ __('home.how3') }}</li>
        </ol>
    </div>

</div>
@endsection
