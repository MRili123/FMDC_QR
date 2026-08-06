@extends('layouts.public')
@section('title', __('wizard.step5.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    <a href="{{ route('reclamation.preuves', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step5.title') }}</h1>
        <p>{{ __('wizard.step5.hint') }}</p>
    </div>

    <form method="POST" action="{{ route('reclamation.resultat', $locale) }}">
        @csrf
        <div class="fmdc-choices">
            @foreach(config('taxonomy.resultats') as $resultat)
                <button type="submit" name="resultat_attendu" value="{{ $resultat }}" class="fmdc-choice">
                    <span class="fmdc-choice__label">{{ __("resultat.$resultat") }}</span>
                </button>
            @endforeach
        </div>
        <button type="submit" name="resultat_attendu" value="" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block mt-3">
            {{ __('wizard.skip') }}
        </button>
    </form>
</div>
@endsection
