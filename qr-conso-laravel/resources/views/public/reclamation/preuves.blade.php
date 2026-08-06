@extends('layouts.public')
@section('title', __('wizard.step4.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    <a href="{{ route('reclamation.decrire', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('wizard.step4.title') }}</h1>
        <p>{{ __('wizard.step4.hint') }}</p>
    </div>

    @error('fichier')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

    <div class="fmdc-card">
        <form method="POST" action="{{ route('attachments.store', $locale) }}" enctype="multipart/form-data">
            @csrf
            <div class="fmdc-field">
                <label for="kind">{{ __('attachment.kind') }}</label>
                <select name="kind" id="kind" class="form-control">
                    @foreach(config('taxonomy.attachment_kinds') as $kind)
                        <option value="{{ $kind }}">{{ __("attachment.$kind") }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fmdc-field">
                <label for="fichier">{{ __('wizard.step4.add') }}</label>
                <input type="file" name="fichier" id="fichier" class="form-control" required
                       accept="image/*,application/pdf,audio/*">
                <small>{{ __('wizard.step4.tooLarge') }}</small>
            </div>
            <button type="submit" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
                <i class="las la-paperclip"></i> {{ __('wizard.step4.add') }}
            </button>
        </form>
    </div>

    @if($attachments->isNotEmpty())
        <div class="fmdc-card">
            <ul class="list-unstyled mb-0">
                @foreach($attachments as $piece)
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <span style="font-size:14px">
                            <i class="las {{ $piece->isImage() ? 'la-image' : ($piece->isAudio() ? 'la-microphone' : 'la-file') }}"></i>
                            {{ $piece->original_name }}
                            <small class="text-muted d-block">{{ __("attachment.{$piece->kind}") }} · {{ round($piece->size / 1024) }} Ko</small>
                        </span>
                        <form method="POST" action="{{ route('attachments.destroy', [$locale, $piece]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger">{{ __('wizard.step4.remove') }}</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('reclamation.preuves', $locale) }}">
        @csrf
        <button type="submit" class="fmdc-btn fmdc-btn--block">
            {{ $attachments->isEmpty() ? __('wizard.skip') : __('wizard.next') }}
        </button>
    </form>
</div>
@endsection
