@extends('layouts.public')
@section('title', __('qr.verifiedTitle'))

@section('content')
<div class="container fmdc-wizard">

    {{-- L'identité de l'établissement est montrée AVANT le formulaire : c'est ce
         qu'un faux QR ne peut pas reproduire sans signature valide (§9). --}}
    <div class="fmdc-verify">
        <i class="las la-shield-alt fmdc-verify__icon"></i>
        <div class="fmdc-verify__title">{{ __('qr.verifiedTitle') }}</div>

        <dl>
            @if($qr->etablissement)
                <dt>{{ __('qr.establishment') }}</dt>
                <dd>{{ $qr->etablissement }}</dd>
            @endif
            @if($qr->secteur)
                <dt>{{ __('qr.sector') }}</dt>
                <dd>{{ __("secteur.{$qr->secteur}") }}</dd>
            @endif
            @if($qr->region)
                <dt>{{ __('qr.region') }}</dt>
                <dd>{{ __("region.{$qr->region}") }}</dd>
            @endif
        </dl>

        <p class="fmdc-verify__host mb-0">
            {{ __('qr.checkDomain', ['domain' => config('qrconso.public_host')]) }}
        </p>
    </div>

    <a href="{{ route('reclamation.start', ['locale' => $locale, 'qr' => $qr->code]) }}"
       class="fmdc-btn fmdc-btn--block">
        {{ __('qr.continue') }}
    </a>

</div>
@endsection
