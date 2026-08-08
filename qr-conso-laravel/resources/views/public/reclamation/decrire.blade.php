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
            <div class="fmdc-field">
                <label for="professionnel">{{ __('wizard.step3.professionnel') }}</label>
                <input id="professionnel" name="professionnel" type="text" class="form-control" maxlength="200"
                       value="{{ old('professionnel', $draft['professionnel'] ?? '') }}">
                <small>{{ __('wizard.step3.professionnelHint') }}</small>
            </div>

            {{-- La région sert à proposer l'association la plus proche. Elle
                 reste facultative : le §7 plafonne à cinq informations
                 obligatoires, et un dossier sans région part au bureau national
                 plutôt que d'être bloqué. Un scan de QR la pré-remplit. --}}
            <div class="fmdc-field mb-0">
                <label for="region">{{ __('wizard.step3.region') }}</label>
                <select id="region" name="region" class="form-control">
                    <option value="">{{ __('wizard.step3.regionNone') }}</option>
                    @foreach(config('taxonomy.regions') as $region)
                        <option value="{{ $region }}"
                            @selected(old('region', $draft['region'] ?? '') === $region)>
                            {{ __("region.$region") }}
                        </option>
                    @endforeach
                </select>
                <small>{{ __('wizard.step3.regionHint') }}</small>
            </div>
        </div>

        <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('wizard.next') }}</button>
    </form>
</div>
@endsection
