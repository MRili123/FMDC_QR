@extends('layouts.public')
@section('title', __('wizard.step0.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    @if(!empty($draft['etablissement']))
        <div class="alert alert-info py-2" style="font-size:14px">
            <i class="las la-store"></i> {{ $draft['etablissement'] }}
        </div>
    @endif

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step0.title') }}</h1>
        <p>{{ __('wizard.step0.hint') }}</p>
    </div>

    @error('demarche')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('reclamation.demarche', $locale) }}">
        @csrf

        {{-- L'anonymat est annoncé ici, avant qu'on ait demandé quoi que ce soit :
             une personne qui craint des représailles doit le savoir au premier
             écran, pas au dernier. --}}
        @foreach([
            'RECLAMATION' => ['la-file-signature', 'demarcheReclamation', 'demarcheReclamationHint', null],
            'SIGNALEMENT' => ['la-bullhorn', 'demarcheSignalement', 'demarcheSignalementHint', 'anonymousBadge'],
            'CONSEIL' => ['la-balance-scale', 'demarcheConseil', 'demarcheConseilHint', null],
        ] as $value => [$icon, $label, $hint, $badge])
            <button type="submit" name="demarche" value="{{ $value }}" class="fmdc-path">
                <span class="fmdc-path__icon"><i class="las {{ $icon }}"></i></span>
                <span class="fmdc-path__body">
                    <span class="fmdc-path__title">
                        {{ __("wizard.step6.$label") }}
                        @if($badge)
                            <span class="fmdc-path__badge">
                                <i class="las la-user-secret"></i> {{ __("wizard.step0.$badge") }}
                            </span>
                        @endif
                    </span>
                    <span class="fmdc-path__hint">{{ __("wizard.step6.$hint") }}</span>
                </span>
                <span class="fmdc-path__arrow"><i class="las la-angle-right"></i></span>
            </button>
        @endforeach
    </form>
</div>
@endsection
