@extends('layouts.public')
@section('title', __('qr.invalidTitle'))

@section('content')
<div class="container fmdc-wizard">

    <div class="fmdc-danger">
        <i class="las la-exclamation-triangle fmdc-danger__icon"></i>
        <h1 style="font-size:19px;font-weight:700;margin:10px 0">{{ __('qr.invalidTitle') }}</h1>
        <p style="font-size:15px">{{ __('qr.invalidBody') }}</p>

        <p class="text-muted" style="font-size:13px">
            {{ __('qr.checkDomain', ['domain' => config('qrconso.public_host')]) }}
        </p>

        @if(session('status'))
            <div class="alert alert-success py-2 mb-0">{{ session('status') }}</div>
        @else
            <form method="POST" action="{{ route('qr.report', $code) }}">
                @csrf
                <button type="submit" class="fmdc-btn fmdc-btn--ghost">
                    <i class="las la-flag"></i> {{ __('qr.invalidReport') }}
                </button>
            </form>
        @endif
    </div>

    {{-- On ne laisse jamais l'utilisateur dans une impasse : le dépôt reste
         possible par la porte nationale, sans établissement pré-rempli. --}}
    <a href="{{ route('reclamation.start', $locale) }}" class="fmdc-btn fmdc-btn--block mt-3">
        {{ __('qr.invalidContinue') }}
    </a>

</div>
@endsection
