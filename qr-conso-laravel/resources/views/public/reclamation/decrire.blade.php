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

    <form method="POST" action="{{ route('reclamation.decrire', $locale) }}" id="describe-form">
        @csrf

        <div class="fmdc-card">
            <div class="fmdc-field">
                <label for="description">{{ __('wizard.step3.tabWrite') }}</label>
                <textarea id="description" name="description" rows="7" class="form-control"
                          placeholder="{{ __('wizard.step3.placeholder') }}"
                          maxlength="5000">{{ old('description', $draft['description'] ?? '') }}</textarea>
                @error('description')<small class="text-danger">{{ $message }}</small>@enderror
            </div>

            @if($aiAvailable)
                <button type="button" id="ai-run" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
                    <i class="las la-magic"></i> <span>{{ __('ia.run') }}</span>
                </button>
            @endif

            <div id="ai-panel" class="fmdc-ai mt-3" hidden>
                <div class="fmdc-ai__head">
                    <i class="las la-robot"></i> {{ __('ia.title') }}
                </div>

                <div id="ai-summary-wrap" hidden>
                    <strong style="font-size:13px">{{ __('ia.summaryTitle') }}</strong>
                    <div class="fmdc-ai__body" id="ai-summary"></div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-primary" id="ai-use">{{ __('ia.useSummary') }}</button>
                        <button type="button" class="btn btn-sm btn-light" id="ai-dismiss">{{ __('ia.keepMine') }}</button>
                    </div>
                </div>

                <div id="ai-missing-wrap" hidden class="mt-3">
                    <strong style="font-size:13px">{{ __('ia.missingTitle') }}</strong>
                    <ul class="fmdc-ai__missing" id="ai-missing"></ul>
                </div>

                <div id="ai-class-wrap" hidden class="mt-2" style="font-size:13px">
                    <span class="text-muted">{{ __('ia.classifiedAs') }} :</span>
                    <span id="ai-class"></span>
                </div>

                <p class="fmdc-ai__note mb-0">
                    <i class="las la-info-circle"></i> {{ __('ia.disclaimer') }}
                    <br><i class="las la-lock"></i> {{ __('ia.local') }}
                </p>
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

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('ai-run');
    if (!btn) return;

    var labels = @json(collect(config('taxonomy.categories'))->mapWithKeys(fn ($c) => [$c => __("categorie.$c")]));
    var motifs = @json(collect(config('taxonomy.motifs'))->mapWithKeys(fn ($m) => [$m => __("motif.$m")]));

    var textarea = document.getElementById('description');
    var panel = document.getElementById('ai-panel');
    var runLabel = btn.querySelector('span');

    btn.addEventListener('click', function () {
        var text = textarea.value.trim();
        if (text.length < 20) { textarea.focus(); return; }

        btn.disabled = true;
        runLabel.textContent = @json(__('ia.running'));

        fetch(@json(route('reclamation.assistance', $locale)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ description: text })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            panel.hidden = false;

            if (data.resume) {
                document.getElementById('ai-summary').textContent = data.resume;
                document.getElementById('ai-summary-wrap').hidden = false;
            }

            if (data.manquant && data.manquant.length) {
                var ul = document.getElementById('ai-missing');
                ul.innerHTML = '';
                data.manquant.forEach(function (item) {
                    var li = document.createElement('li');
                    li.textContent = item;
                    ul.appendChild(li);
                });
                document.getElementById('ai-missing-wrap').hidden = false;
            }

            if (data.classification && data.classification.categorie) {
                var parts = [labels[data.classification.categorie] || data.classification.categorie];
                if (data.classification.motif) parts.push(motifs[data.classification.motif] || data.classification.motif);
                document.getElementById('ai-class').textContent = parts.join(' — ');
                document.getElementById('ai-class-wrap').hidden = false;
            }

            if (!data.resume && !data.manquant && !data.classification) {
                panel.querySelector('.fmdc-ai__body') || (panel.hidden = true);
                alert(@json(__('ia.unavailable')));
            }
        })
        .catch(function () { alert(@json(__('ia.unavailable'))); })
        .finally(function () {
            btn.disabled = false;
            runLabel.textContent = @json(__('ia.run'));
        });
    });

    // Le remplacement est un choix explicite : le texte original n'est jamais
    // écrasé sans que la personne l'ait demandé (§8).
    document.getElementById('ai-use').addEventListener('click', function () {
        textarea.value = document.getElementById('ai-summary').textContent;
        panel.hidden = true;
    });
    document.getElementById('ai-dismiss').addEventListener('click', function () {
        panel.hidden = true;
    });
})();
</script>
@endpush
