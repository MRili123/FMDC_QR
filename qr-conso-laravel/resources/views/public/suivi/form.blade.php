@extends('layouts.public')
@section('title', __('track.title'))

@section('content')
<div class="container fmdc-wizard">

    <div class="fmdc-step-head">
        <h1>{{ __('track.title') }}</h1>
        <p>{{ __('track.hint') }}</p>
    </div>

    @if(!empty($error))
        <div class="alert alert-danger py-2">{{ $error }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger py-2">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('suivi.lookup', $locale) }}" class="fmdc-card">
        @csrf
        <div class="fmdc-field">
            <label for="reference">{{ __('track.reference') }}</label>
            <input id="reference" name="reference" class="form-control" required
                   placeholder="FMDC-2026-000001" value="{{ old('reference', $reference ?? '') }}">
        </div>
        <div class="fmdc-field">
            <label for="token">{{ __('track.token') }}</label>
            <input id="token" name="token" class="form-control" required>
        </div>
        <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('track.submit') }}</button>
    </form>

</div>
@endsection
