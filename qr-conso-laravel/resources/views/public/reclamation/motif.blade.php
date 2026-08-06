@extends('layouts.public')
@section('title', __('wizard.step2.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    <a href="{{ route('reclamation.categorie', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step2.title') }}</h1>
        <p>{{ __('wizard.step2.hint') }}</p>
    </div>

    @error('motif')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <form method="POST" action="{{ route('reclamation.motif', $locale) }}">
        @csrf
        <div class="fmdc-choices">
            @foreach(config('taxonomy.motifs') as $motif)
                <button type="submit" name="motif" value="{{ $motif }}"
                        class="fmdc-choice {{ ($draft['motif'] ?? null) === $motif ? 'border-primary' : '' }}">
                    <span class="fmdc-choice__label">{{ __("motif.$motif") }}</span>
                </button>
            @endforeach
        </div>
    </form>
</div>
@endsection
