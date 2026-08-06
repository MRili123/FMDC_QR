@extends('layouts.public')
@section('title', __('wizard.orient.aideTitle'))

@section('content')
<div class="container fmdc-wizard">

    @if(!empty($draft['etablissement']))
        <div class="alert alert-info py-2" style="font-size:14px">
            <i class="las la-store"></i> {{ $draft['etablissement'] }}
        </div>
    @endif

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.orient.aideTitle') }}</h1>
        <p>{{ __('wizard.orient.aideHint') }}</p>
    </div>

    @error('choix')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('reclamation.aide', $locale) }}">
        @csrf

        {{-- Le conseil se sépare en premier : il n'ouvre pas de litige, donc
             rien de ce qui suit (professionnel, preuves, suites) ne s'applique. --}}
        <button type="submit" name="choix" value="CONSEIL" class="fmdc-path">
            <span class="fmdc-path__icon">💡</span>
            <span class="fmdc-path__body">
                <span class="fmdc-path__title">{{ __('wizard.orient.conseil') }}</span>
                <span class="fmdc-path__hint">{{ __('wizard.orient.conseilHint') }}</span>
            </span>
            <span class="fmdc-path__arrow"><i class="las la-angle-right"></i></span>
        </button>

        <button type="submit" name="choix" value="DEMANDE" class="fmdc-path">
            <span class="fmdc-path__icon">📄</span>
            <span class="fmdc-path__body">
                <span class="fmdc-path__title">{{ __('wizard.orient.demande') }}</span>
                <span class="fmdc-path__hint">{{ __('wizard.orient.demandeHint') }}</span>
            </span>
            <span class="fmdc-path__arrow"><i class="las la-angle-right"></i></span>
        </button>
    </form>

</div>
@endsection
