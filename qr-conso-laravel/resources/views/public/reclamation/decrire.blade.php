@extends('layouts.public')
@section('title', __('wizard.step3.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    <a href="{{ route('reclamation.motif', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step3.title') }}</h1>
        <p>{{ __('wizard.step3.hint') }}</p>
    </div>

    {{-- Aucune assistance IA à cet endroit : le consommateur raconte avec ses
         mots, et c'est ce récit brut qui fait foi. Le résumé est produit côté
         back-office, pour l'agent qui traite le dossier. --}}
    <form method="POST" action="{{ route('reclamation.decrire', $locale) }}" id="describe-form">
        @csrf

        <div class="fmdc-card">
            <div class="fmdc-field mb-0">
                <label for="description">{{ __('wizard.step3.tabWrite') }}</label>
                <textarea id="description" name="description" rows="7" class="form-control"
                          placeholder="{{ __('wizard.step3.placeholder') }}"
                          maxlength="5000">{{ old('description', $draft['description'] ?? '') }}</textarea>
                @error('description')<small class="text-danger">{{ $message }}</small>@enderror
            </div>
        </div>

        <div class="fmdc-card">
            <div class="fmdc-field mb-0">
                <label for="professionnel">{{ __('wizard.step3.professionnel') }}</label>
                <input id="professionnel" name="professionnel" type="text" class="form-control" maxlength="200"
                       value="{{ old('professionnel', $draft['professionnel'] ?? '') }}">
                <small>{{ __('wizard.step3.professionnelHint') }}</small>
            </div>
        </div>

        <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('wizard.next') }}</button>
    </form>
</div>
@endsection
