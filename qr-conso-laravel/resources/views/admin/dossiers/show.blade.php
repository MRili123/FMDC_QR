@extends('layouts.admin')
@section('title', $dossier->reference)

@section('content')
<h1 class="fmdc-page-title">
    <span class="fmdc-code">{{ $dossier->reference }}</span>
    <span class="fmdc-status ml-2">{{ __("status.{$dossier->status}") }}</span>
</h1>

<div class="row">
    <div class="col-lg-8">

        <div class="fmdc-stat mb-3">
            <h2 style="font-size:16px;font-weight:600;margin-bottom:14px">{{ __('admin.dossier.content') }}</h2>

            <dl class="row fmdc-no-flip mb-0" style="font-size:14px">
                <dt class="col-sm-4 text-muted">{{ __('admin.dossier.category') }}</dt>
                <dd class="col-sm-8">{{ __("categorie.{$dossier->categorie}") }}</dd>

                <dt class="col-sm-4 text-muted">{{ __('admin.dossier.motif') }}</dt>
                <dd class="col-sm-8">{{ __("motif.{$dossier->motif}") }}</dd>

                @if($dossier->resultat_attendu)
                    <dt class="col-sm-4 text-muted">{{ __('admin.dossier.expected') }}</dt>
                    <dd class="col-sm-8">{{ __("resultat.{$dossier->resultat_attendu}") }}</dd>
                @endif

                @if($dossier->professionnel)
                    <dt class="col-sm-4 text-muted">{{ __('admin.dossier.professionnel') }}</dt>
                    <dd class="col-sm-8">{{ $dossier->professionnel }}</dd>
                @endif

                @if($dossier->etablissement)
                    <dt class="col-sm-4 text-muted">{{ __('admin.dossier.etablissement') }}</dt>
                    <dd class="col-sm-8">{{ $dossier->etablissement }}</dd>
                @endif

                @if($dossier->region)
                    <dt class="col-sm-4 text-muted">{{ __('admin.dossier.region') }}</dt>
                    <dd class="col-sm-8">{{ __("region.{$dossier->region}") }}</dd>
                @endif

                <dt class="col-sm-4 text-muted">{{ __('admin.dossier.source') }}</dt>
                <dd class="col-sm-8">
                    {{ $dossier->qrCode
                        ? __('admin.dossier.sourceQr', ['libelle' => $dossier->qrCode->libelle])
                        : __('admin.dossier.sourceDirect') }}
                </dd>
            </dl>

            <hr>
            <div class="text-muted mb-1" style="font-size:13px">{{ __('admin.dossier.description') }}</div>
            @if($dossier->description)
                <p style="white-space:pre-wrap;font-size:15px;margin-bottom:0">{{ $dossier->description }}</p>
            @else
                <p class="text-muted mb-0">{{ __('admin.dossier.noDescription') }}</p>
            @endif

            {{-- L'assistance IA n'existe que de ce côté : le consommateur écrit
                 librement, l'agent obtient de quoi traiter plus vite. --}}
            @if($aiAvailable && $dossier->description)
                <button type="button" id="ai-run" class="btn btn-sm btn-outline-primary mt-3">
                    <i class="las la-magic"></i> <span>{{ __('ia.suggest') }}</span>
                </button>
                <div id="ai-panel" class="fmdc-ai mt-3" hidden>
                    <div class="fmdc-ai__head"><i class="las la-robot"></i> {{ __('ia.title') }}</div>

                    <strong style="font-size:13px">{{ __('ia.summaryTitle') }}</strong>
                    <div id="ai-summary" class="fmdc-ai__body"></div>

                    <div id="ai-class-wrap" hidden class="mt-2" style="font-size:13px">
                        <span class="text-muted">{{ __('ia.classifiedAs') }} :</span> <span id="ai-class"></span>
                    </div>
                    <div id="ai-missing-wrap" hidden class="mt-2">
                        <strong style="font-size:13px">{{ __('ia.missingTitle') }}</strong>
                        <ul class="fmdc-ai__missing" id="ai-missing"></ul>
                    </div>

                    <p class="fmdc-ai__note mb-0">
                        <i class="las la-info-circle"></i> {{ __('ia.disclaimer') }}
                        <br><i class="las la-lock"></i> {{ __('ia.local') }}
                    </p>
                </div>
            @elseif($dossier->description && !$aiAvailable)
                <p class="text-muted mt-3 mb-0" style="font-size:13px">
                    <i class="las la-robot"></i> {{ __('ia.unavailable') }}
                </p>
            @endif
        </div>

        <div class="fmdc-stat mb-3">
            <h2 style="font-size:16px;font-weight:600;margin-bottom:4px">{{ __('admin.dossier.attachments') }}</h2>
            <p class="text-muted" style="font-size:13px">{{ __('admin.piece.hint') }}</p>

            @forelse($dossier->attachments as $piece)
                @php($extractible = $piece->isImage() || $piece->isAudio())
                <div class="fmdc-piece" data-piece="{{ $piece->id }}">
                    <div class="d-flex align-items-center" style="gap:10px">
                        <i class="las {{ $piece->isImage() ? 'la-image' : ($piece->isAudio() ? 'la-microphone' : 'la-file') }}"
                           style="font-size:22px;color:var(--fmdc-primary)"></i>
                        <span class="flex-grow-1" style="font-size:14px;min-width:0">
                            {{ $piece->original_name }}
                            <small class="text-muted d-block">
                                {{ __("attachment.{$piece->kind}") }} · {{ round($piece->size / 1024) }} Ko
                            </small>
                        </span>

                        {{-- Ouvrir reste possible, mais devient facultatif : le
                             texte extrait suffit dans la plupart des cas. --}}
                        <a href="{{ route('admin.attachments.show', [$locale, $piece]) }}" target="_blank"
                           class="btn btn-sm btn-outline-primary" style="white-space:nowrap">
                            <i class="las la-external-link-alt"></i> {{ __('admin.piece.open') }}
                        </a>

                        @if($extractible && $extractionAvailable)
                            <button type="button" class="btn btn-sm btn-outline-secondary fmdc-extract"
                                    data-url="{{ route('admin.attachments.extract', [$locale, $piece]) }}"
                                    style="white-space:nowrap">
                                <i class="las la-font"></i>
                                <span>{{ $piece->isAudio() ? __('admin.piece.transcribe') : __('admin.piece.ocr') }}</span>
                            </button>
                        @endif
                    </div>

                    @if($piece->isImage())
                        <img src="{{ route('admin.attachments.show', [$locale, $piece]) }}"
                             alt="{{ $piece->original_name }}" class="fmdc-piece__apercu" loading="lazy">
                    @elseif($piece->isAudio())
                        <audio controls preload="none" class="fmdc-piece__audio">
                            <source src="{{ route('admin.attachments.show', [$locale, $piece]) }}"
                                    type="{{ $piece->mime_type }}">
                        </audio>
                    @endif

                    <div class="fmdc-piece__texte" @if(! $piece->transcription) hidden @endif>
                        <strong style="font-size:12px">{{ __('admin.piece.extracted') }}</strong>
                        <p class="mb-0">{{ $piece->transcription }}</p>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">{{ __('admin.dossier.noAttachments') }}</p>
            @endforelse
        </div>

        <div class="fmdc-stat">
            <h2 style="font-size:16px;font-weight:600;margin-bottom:14px">{{ __('admin.dossier.history') }}</h2>
            <ul class="fmdc-timeline">
                @foreach($events as $event)
                    <li>
                        <time>{{ $event->created_at->translatedFormat('d M Y — H:i') }}</time>
                        @if($event->to_status)<strong>{{ __("status.{$event->to_status}") }}</strong>@endif
                        @if($event->note)<span style="font-size:14px">{{ $event->note }}</span>@endif
                        <small class="text-muted">
                            {{ $event->actor_label }}
                            @unless($event->public_note)
                                · <i class="las la-lock"></i> {{ __('admin.dossier.notePrivate') }}
                            @endunless
                        </small>
                    </li>
                @endforeach
            </ul>
        </div>

    </div>

    <div class="col-lg-4">

        {{-- L'identité vit dans une table séparée (§9) et n'est affichée que sur
             cette fiche, jamais dans les listes ni les tableaux de bord. --}}
        <div class="fmdc-stat mb-3">
            <h2 style="font-size:16px;font-weight:600;margin-bottom:12px">{{ __('admin.dossier.identity') }}</h2>
            @if($dossier->requerant)
                <dl class="mb-0" style="font-size:14px">
                    @if($dossier->requerant->nom)
                        <dt class="text-muted">{{ __('admin.dossier.name') }}</dt>
                        <dd>{{ $dossier->requerant->nom }}</dd>
                    @endif
                    @if($dossier->requerant->telephone)
                        <dt class="text-muted">{{ __('admin.dossier.phone') }}</dt>
                        <dd>
                            {{ $dossier->requerant->telephone }}
                            @if($dossier->requerant->phone_verified_at)
                                <span class="badge badge-success">{{ __('admin.dossier.verified') }}</span>
                            @endif
                        </dd>
                    @endif
                    @if($dossier->requerant->email)
                        <dt class="text-muted">{{ __('admin.dossier.email') }}</dt>
                        <dd>{{ $dossier->requerant->email }}</dd>
                    @endif
                </dl>
            @else
                <p class="text-muted mb-0"><i class="las la-user-secret"></i> {{ __('admin.dossier.identityAnonymous') }}</p>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.dossiers.status', [$locale, $dossier]) }}" class="fmdc-stat mb-3">
            @csrf
            <h2 style="font-size:16px;font-weight:600;margin-bottom:12px">{{ __('admin.dossier.changeStatus') }}</h2>

            <div class="fmdc-field">
                <label for="status">{{ __('admin.dossier.newStatus') }}</label>
                <select name="status" id="status" class="form-control">
                    @foreach(config('taxonomy.statuses') as $status)
                        <option value="{{ $status }}" @selected($dossier->status === $status)>
                            {{ __("status.$status") }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="note">{{ __('admin.dossier.note') }}</label>
                <textarea name="note" id="note" rows="3" class="form-control" maxlength="2000"></textarea>
            </div>

            {{-- Décocher rend la note interne : le consommateur ne la voit pas
                 sur sa page de suivi. --}}
            <label class="d-flex align-items-center mb-3" style="gap:8px;font-size:14px;cursor:pointer">
                <input type="checkbox" name="public_note" value="1" checked>
                {{ __('admin.dossier.noteHint') }}
            </label>

            <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('admin.dossier.apply') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.dossiers.assign', [$locale, $dossier]) }}" class="fmdc-stat">
            @csrf
            <h2 style="font-size:16px;font-weight:600;margin-bottom:12px">{{ __('admin.dossier.reassign') }}</h2>
            <select name="association_id" class="form-control mb-3">
                <option value="">{{ __('admin.queue.unassigned') }}</option>
                @foreach($associations as $association)
                    <option value="{{ $association->id }}" @selected($dossier->assigned_association_id === $association->id)>
                        {{ $association->nom }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">{{ __('admin.dossier.apply') }}</button>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var token = document.querySelector('meta[name=csrf-token]').content;

    document.querySelectorAll('.fmdc-extract').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var bloc = btn.closest('.fmdc-piece');
            var cible = bloc.querySelector('.fmdc-piece__texte');
            var libelle = btn.querySelector('span');
            var initial = libelle.textContent;

            btn.disabled = true;
            libelle.textContent = @json(__('admin.piece.working'));

            fetch(btn.dataset.url, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                cible.hidden = false;
                if (d.ok) {
                    cible.querySelector('p').textContent = d.text;
                } else {
                    cible.querySelector('p').textContent =
                        @json(__('admin.piece.failed')) + ' (' + (d.error || '?') + ')';
                }
            })
            .catch(function () {
                cible.hidden = false;
                cible.querySelector('p').textContent = @json(__('admin.piece.failed'));
            })
            .finally(function () {
                btn.disabled = false;
                libelle.textContent = initial;
            });
        });
    });
})();
</script>
@if($aiAvailable && $dossier->description)
<script>
(function () {
    var btn = document.getElementById('ai-run');
    var panel = document.getElementById('ai-panel');
    var label = btn.querySelector('span');
    var cats = @json(collect(config('taxonomy.categories'))->mapWithKeys(fn ($c) => [$c => __("categorie.$c")]));

    btn.addEventListener('click', function () {
        btn.disabled = true;
        label.textContent = @json(__('ia.running'));

        fetch(@json(route('admin.dossiers.ai', [$locale, $dossier])), {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'}
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            panel.hidden = false;
            document.getElementById('ai-summary').textContent = data.resume || '';

            if (data.classification && data.classification.categorie) {
                document.getElementById('ai-class').textContent =
                    (cats[data.classification.categorie] || data.classification.categorie) +
                    (data.classification.urgence ? ' — ' + data.classification.urgence : '');
                document.getElementById('ai-class-wrap').hidden = false;
            }
            if (data.manquant && data.manquant.length) {
                var ul = document.getElementById('ai-missing');
                ul.innerHTML = '';
                data.manquant.forEach(function (m) {
                    var li = document.createElement('li'); li.textContent = m; ul.appendChild(li);
                });
                document.getElementById('ai-missing-wrap').hidden = false;
            }
        })
        .catch(function () { alert(@json(__('ia.unavailable'))); })
        .finally(function () {
            btn.disabled = false;
            label.textContent = @json(__('ia.suggest'));
        });
    });
})();
</script>
@endif
@endpush
