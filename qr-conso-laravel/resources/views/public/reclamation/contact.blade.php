@extends('layouts.public')
@section('title', ($draft['demarche'] ?? null) === 'SIGNALEMENT'
    ? __('wizard.orient.contactTitleSignalement')
    : __('wizard.step6.title'))

@section('content')
<div class="container fmdc-wizard">
    @include('public.partials.progress')

    <a href="{{ route('reclamation.resultat', $locale) }}" class="fmdc-back">
        <i class="las la-arrow-left"></i> {{ __('wizard.back') }}
    </a>

    @php($demarche = $draft['demarche'] ?? 'RECLAMATION')
    @php($estSignalement = $demarche === 'SIGNALEMENT')

    <div class="fmdc-step-head">
        <h1>{{ $estSignalement ? __('wizard.orient.contactTitleSignalement') : __('wizard.step6.title') }}</h1>
        <p>{{ $estSignalement ? __('wizard.orient.contactHintSignalement') : __('wizard.step6.hint') }}</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('reclamation.submit', $locale) }}" id="contact-form">
        @csrf

        {{-- La démarche a été choisie au premier écran : on la rappelle sans
             permettre de la changer ici, pour que la promesse faite à l'entrée
             ne puisse pas être défaite à la sortie. --}}
        <div class="fmdc-card d-flex align-items-center" style="gap:12px">
            <i class="las {{ $estSignalement ? 'la-bullhorn' : ($demarche === 'CONSEIL' ? 'la-balance-scale' : 'la-file-signature') }}"
               style="font-size:26px;color:var(--fmdc-primary)"></i>
            <span>
                <strong style="font-size:15px">
                    {{ __('wizard.orient.'.($estSignalement ? 'signalement' : ($demarche === 'CONSEIL' ? 'conseil' : 'reclamation'))) }}
                </strong>
                <a href="{{ route('reclamation.aide', $locale) }}" class="d-block" style="font-size:13px">
                    {{ __('wizard.orient.change') }}
                </a>
            </span>
        </div>

        <div class="fmdc-card" id="contact-fields"
             @if($estSignalement) style="display:none" @endif>
            <div class="fmdc-field">
                <label for="telephone">{{ __('wizard.step6.phone') }}</label>
                <div class="d-flex" style="gap:8px">
                    <input id="telephone" name="telephone" type="tel" class="form-control"
                           placeholder="{{ __('wizard.step6.phonePlaceholder') }}" value="{{ old('telephone') }}">
                    <button type="button" id="otp-send" class="btn btn-outline-primary" style="white-space:nowrap">
                        {{ __('wizard.step6.sendCode') }}
                    </button>
                </div>
            </div>

            <div class="fmdc-field" id="otp-block" hidden>
                <label for="otp-code">{{ __('wizard.step6.codeSent') }}</label>
                <div class="d-flex" style="gap:8px">
                    <input id="otp-code" type="text" inputmode="numeric" maxlength="6" class="form-control"
                           placeholder="{{ __('wizard.step6.codePlaceholder') }}">
                    <button type="button" id="otp-verify" class="btn btn-outline-primary">
                        {{ __('wizard.step6.verify') }}
                    </button>
                </div>
                <small id="otp-feedback"></small>
            </div>

            <div class="fmdc-field">
                <label for="email">{{ __('wizard.step6.email') }}</label>
                <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}">
            </div>

            <div class="fmdc-field mb-0">
                <label for="nom">{{ __('wizard.step6.name') }}</label>
                <input id="nom" name="nom" type="text" class="form-control" value="{{ old('nom') }}">
                <small>{{ __('wizard.step6.nameHint') }}</small>
            </div>
        </div>

        {{-- Pour un signalement l'anonymat est le défaut, pas une case à
             débusquer : c'est la démarche que le §7 décrit comme pouvant se
             passer d'identité. --}}
        <div class="fmdc-card {{ $estSignalement ? 'fmdc-card--accent' : '' }}">
            <label class="d-flex align-items-start" style="gap:10px;cursor:pointer">
                <input type="checkbox" name="anonyme" value="1" id="anonyme" class="mt-1"
                       @checked($estSignalement)>
                <span>
                    <strong style="font-size:15px">{{ __('wizard.step6.anonymous') }}</strong>
                    <small class="d-block text-muted">{{ __('wizard.step6.anonymousNote') }}</small>
                </span>
            </label>
        </div>

        <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('wizard.submit') }}</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var token = document.querySelector('meta[name=csrf-token]').content;
    var phone = document.getElementById('telephone');
    var block = document.getElementById('otp-block');
    var feedback = document.getElementById('otp-feedback');

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json'},
            body: JSON.stringify(body)
        });
    }

    document.getElementById('otp-send').addEventListener('click', function () {
        if (!phone.value.trim()) { phone.focus(); return; }
        post(@json(route('otp.request', $locale)), {telephone: phone.value})
            .then(function () { block.hidden = false; document.getElementById('otp-code').focus(); });
    });

    document.getElementById('otp-verify').addEventListener('click', function () {
        var code = document.getElementById('otp-code').value;
        post(@json(route('otp.verify', $locale)), {telephone: phone.value, code: code})
            .then(function (r) { return r.json().then(function (d) { return {ok: r.ok, data: d}; }); })
            .then(function (res) {
                if (res.ok && res.data.verified) {
                    feedback.textContent = @json(__('wizard.step6.verified'));
                    feedback.className = 'text-success';
                } else {
                    feedback.textContent = @json(__('wizard.step6.codeInvalid'));
                    feedback.className = 'text-danger';
                }
            });
    });

    // Cocher « anonyme » doit visiblement retirer la demande de coordonnées,
    // sinon la promesse d'anonymat n'est pas crédible.
    var anonyme = document.getElementById('anonyme');
    anonyme.addEventListener('change', function () {
        document.getElementById('contact-fields').style.display = anonyme.checked ? 'none' : '';
    });
})();
</script>
@endpush
