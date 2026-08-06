@extends('layouts.public')
@section('title', __('conseil.title'))

@section('content')
<div class="container fmdc-wizard">

    <a href="{{ route('reclamation.aide', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    <div class="fmdc-step-head">
        <h1>{{ __('conseil.title') }}</h1>
        <p>{{ __('conseil.hint') }}</p>
    </div>

    @unless($disponible)
        <div class="alert alert-warning py-2">{{ __('conseil.unavailable') }}</div>
    @endunless

    <div class="fmdc-chat" id="chat" aria-live="polite">
        <div class="fmdc-chat__msg fmdc-chat__msg--bot">
            {{ __('conseil.greeting') }}
        </div>
    </div>

    @if($disponible)
        <div class="fmdc-suggestions" id="suggestions">
            @foreach($suggestions as $suggestion)
                <button type="button" class="fmdc-chip" data-question="{{ $suggestion }}">{{ $suggestion }}</button>
            @endforeach
        </div>

        <form id="conseil-form" class="fmdc-chat__form">
            @csrf
            <input type="text" id="question" name="question" class="form-control"
                   placeholder="{{ __('conseil.placeholder') }}"
                   maxlength="{{ config('conseil.max_question') }}" autocomplete="off" required>
            <button type="submit" id="send" class="fmdc-btn" aria-label="{{ __('conseil.send') }}">
                <i class="las la-paper-plane"></i>
            </button>
        </form>
    @endif

    {{-- Le conseil informe ; il ne remplace ni un avocat ni le dépôt d'un
         dossier. Le §8 interdit toute décision juridique autonome. --}}
    <p class="fmdc-chat__note">
        <i class="las la-info-circle"></i> {{ __('conseil.disclaimer') }}
    </p>

    <div class="fmdc-card text-center">
        <p class="mb-2" style="font-size:14px">{{ __('conseil.escalateHint') }}</p>
        <a href="{{ route('reclamation.demarche', $locale) }}" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
            {{ __('conseil.escalate') }}
        </a>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    var form = document.getElementById('conseil-form');
    if (!form) return;

    var chat = document.getElementById('chat');
    var input = document.getElementById('question');
    var send = document.getElementById('send');
    var token = document.querySelector('meta[name=csrf-token]').content;
    var url = @json(route('conseil.repondre', $locale));

    function bulle(classe, texte) {
        var div = document.createElement('div');
        div.className = 'fmdc-chat__msg ' + classe;
        div.textContent = texte;
        chat.appendChild(div);
        chat.scrollTop = chat.scrollHeight;
        return div;
    }

    document.querySelectorAll('.fmdc-chip').forEach(function (chip) {
        chip.addEventListener('click', function () {
            input.value = chip.dataset.question;
            form.requestSubmit();
        });
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var question = input.value.trim();
        if (question.length < 5) return;

        bulle('fmdc-chat__msg--user', question);
        input.value = '';
        input.disabled = send.disabled = true;

        var reponse = bulle('fmdc-chat__msg--bot fmdc-chat__msg--typing', @json(__('conseil.thinking')));

        var data = new FormData();
        data.append('_token', token);
        data.append('question', question);

        fetch(url, {method: 'POST', body: data})
            .then(function (r) {
                if (!r.ok || !r.body) throw new Error('stream');
                reponse.textContent = '';
                reponse.classList.remove('fmdc-chat__msg--typing');

                // Lecture au fil de l'eau : le texte apparaît pendant que le
                // modèle écrit, au lieu d'un écran figé pendant vingt secondes.
                var reader = r.body.getReader();
                var decoder = new TextDecoder();

                return (function lire() {
                    return reader.read().then(function (res) {
                        if (res.done) return;
                        reponse.textContent += decoder.decode(res.value, {stream: true});
                        chat.scrollTop = chat.scrollHeight;
                        return lire();
                    });
                })();
            })
            .catch(function () {
                reponse.classList.remove('fmdc-chat__msg--typing');
                reponse.textContent = @json(__('conseil.error'));
            })
            .finally(function () {
                input.disabled = send.disabled = false;
                input.focus();
                var s = document.getElementById('suggestions');
                if (s) s.remove();
            });
    });
})();
</script>
@endpush
