@extends('layouts.public')
@section('title', __('wizard.step1.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    @if(!empty($draft['etablissement']))
        <div class="alert alert-info py-2" style="font-size:14px">
            <i class="las la-store"></i> {{ $draft['etablissement'] }}
        </div>
    @endif

    <a href="{{ ($draft['demarche'] ?? null) === 'CONSEIL'
                 ? route('reclamation.aide', $locale)
                 : route('reclamation.demarche', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step1.title') }}</h1>
        <p>{{ __('wizard.step1.hint') }}</p>
    </div>

    @error('categorie')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('reclamation.categorie', $locale) }}">
        @csrf
        <div class="fmdc-choices">
            @foreach(config('taxonomy.categories') as $categorie)
                <button type="submit" name="categorie" value="{{ $categorie }}"
                        class="fmdc-choice {{ ($draft['categorie'] ?? null) === $categorie ? 'border-primary' : '' }}">
                    <span class="fmdc-choice__icon">{{ config("taxonomy.category_icons.$categorie") }}</span>
                    <span class="fmdc-choice__label">{{ __("categorie.$categorie") }}</span>
                </button>
            @endforeach
        </div>
    </form>
</div>
@endsection
