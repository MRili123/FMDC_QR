@extends('layouts.public')
@section('title', __('wizard.step7.title'))

@php($trackUrl = route('suivi.show', ['locale' => $locale, 'reference' => $reference, 't' => $token]))

@section('content')
<div class="container fmdc-wizard">

    <div class="fmdc-card text-center">
        <i class="las la-check-circle" style="font-size:46px;color:#1f9d55"></i>
        <h1 style="font-size:21px;font-weight:700;margin:10px 0 18px">{{ __('wizard.step7.title') }}</h1>

        <div class="text-muted" style="font-size:13px">{{ __('wizard.step7.reference') }}</div>
        <div class="fmdc-ref my-2">{{ $reference }}</div>
        <p class="text-muted" style="font-size:14px">{{ __('wizard.step7.keepIt') }}</p>
    </div>

    <div class="fmdc-card">
        <div style="font-weight:600;font-size:14px;margin-bottom:6px">{{ __('wizard.step7.trackLink') }}</div>
        {{-- Le lien porte le jeton : c'est lui, et non la référence seule, qui
             autorise la lecture du dossier. --}}
        <input type="text" class="form-control" readonly value="{{ $trackUrl }}"
               onclick="this.select()" style="font-size:12px">
    </div>

    <a href="{{ $trackUrl }}" class="fmdc-btn fmdc-btn--block mb-2">
        <i class="las la-search"></i> {{ __('wizard.step7.track') }}
    </a>
    <a href="{{ route('reclamation.start', $locale) }}" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
        {{ __('wizard.step7.newRequest') }}
    </a>

</div>
@endsection
