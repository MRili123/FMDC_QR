@extends('layouts.public')
@section('title', __('wizard.orient.demarcheTitle'))

@section('content')
<div class="container fmdc-wizard">

    <a href="{{ route('reclamation.aide', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    @if(!empty($draft['etablissement']))
        <div class="alert alert-info py-2" style="font-size:14px">
            <i class="las la-store"></i> {{ $draft['etablissement'] }}
        </div>
    @endif

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.orient.demarcheTitle') }}</h1>
        <p>{{ __('wizard.orient.demarcheHint') }}</p>
    </div>

    @error('demarche')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('reclamation.demarche', $locale) }}">
        @csrf

        {{-- L'anonymat est annoncé ici, avant qu'on ait demandé quoi que ce
             soit : c'est ce qui distingue le signalement de la réclamation. --}}
        <button type="submit" name="demarche" value="SIGNALEMENT" class="fmdc-path">
            <span class="fmdc-path__icon"><i class="las la-bullhorn"></i></span>
            <span class="fmdc-path__body">
                <span class="fmdc-path__title">
                    {{ __('wizard.orient.signalement') }}
                    <span class="fmdc-path__badge">
                        <i class="las la-user-secret"></i> {{ __('wizard.orient.anonymousBadge') }}
                    </span>
                </span>
                <span class="fmdc-path__hint">{{ __('wizard.orient.signalementHint') }}</span>
            </span>
            <span class="fmdc-path__arrow"><i class="las la-angle-right"></i></span>
        </button>

        <button type="submit" name="demarche" value="RECLAMATION" class="fmdc-path">
            <span class="fmdc-path__icon"><i class="las la-file-signature"></i></span>
            <span class="fmdc-path__body">
                <span class="fmdc-path__title">{{ __('wizard.orient.reclamation') }}</span>
                <span class="fmdc-path__hint">{{ __('wizard.orient.reclamationHint') }}</span>
            </span>
            <span class="fmdc-path__arrow"><i class="las la-angle-right"></i></span>
        </button>
    </form>

</div>
@endsection
