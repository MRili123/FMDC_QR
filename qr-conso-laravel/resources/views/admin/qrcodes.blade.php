@extends('layouts.admin')
@section('title', __('admin.qr.title'))

@section('content')
<h1 class="fmdc-page-title">{{ __('admin.qr.title') }}</h1>

<div class="row">
    <div class="col-lg-8">
        <div class="fmdc-table table-responsive">
            <table class="table fmdc-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.qr.libelle') }}</th>
                        <th>{{ __('admin.qr.type') }}</th>
                        <th>{{ __('admin.qr.scans') }}</th>
                        <th>{{ __('admin.qr.reported') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($codes as $qr)
                        <tr class="{{ $qr->active ? '' : 'text-muted' }}">
                            <td>
                                <strong>{{ $qr->libelle }}</strong>
                                <small class="d-block text-muted fmdc-code">/r/{{ $qr->code }}</small>
                                @unless($qr->active)
                                    <span class="badge badge-secondary">{{ __('admin.qr.inactive') }}</span>
                                @endunless
                            </td>
                            <td>{{ $qr->type }}</td>
                            <td>{{ $qr->scan_count }}</td>
                            <td>
                                @if($qr->reported_count > 0)
                                    <span class="text-danger"><strong>{{ $qr->reported_count }}</strong></span>
                                @else
                                    0
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                <a href="{{ route('admin.qrcodes.poster', [$locale, $qr]) }}" target="_blank"
                                   class="btn btn-sm btn-outline-primary">{{ __('admin.qr.download') }}</a>
                                <form method="POST" action="{{ route('admin.qrcodes.toggle', [$locale, $qr]) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-link">
                                        {{ $qr->active ? __('admin.qr.deactivate') : __('admin.qr.activate') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.qrcodes.store', $locale) }}" class="fmdc-stat" id="qr-form">
            @csrf
            <h2 style="font-size:15px;font-weight:600;margin-bottom:12px">{{ __('admin.qr.create') }}</h2>

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                </div>
            @endif

            <div class="fmdc-field">
                <label for="type">{{ __('admin.qr.type') }}</label>
                <select id="type" name="type" class="form-control" required>
                    @foreach(['NATIONAL', 'SECTORIEL', 'ETABLISSEMENT'] as $type)
                        <option value="{{ $type }}" @selected(old('type') === $type)>
                            {{ __("admin.qr.type$type") }}
                        </option>
                    @endforeach
                </select>
                <small id="type-hint" class="text-muted"></small>
            </div>

            <div class="fmdc-field">
                <label for="libelle">{{ __('admin.qr.libelle') }}</label>
                <input id="libelle" name="libelle" class="form-control" required value="{{ old('libelle') }}">
            </div>

            {{-- Les champs sans objet pour le type choisi sont désactivés plutôt
                 que masqués : l'agent voit que le champ existe et pourquoi il ne
                 s'applique pas ici. --}}
            <div class="fmdc-field" data-champ="etablissement">
                <label for="etablissement">
                    {{ __('admin.qr.etablissement') }}
                    <span class="fmdc-req" hidden>*</span>
                </label>
                <input id="etablissement" name="etablissement" class="form-control" value="{{ old('etablissement') }}">
            </div>

            <div class="fmdc-field" data-champ="secteur">
                <label for="secteur">
                    {{ __('admin.qr.secteur') }}
                    <span class="fmdc-req" hidden>*</span>
                </label>
                <select id="secteur" name="secteur" class="form-control">
                    <option value="">{{ __('admin.qr.none') }}</option>
                    @foreach(config('taxonomy.secteurs') as $secteur)
                        <option value="{{ $secteur }}" @selected(old('secteur') === $secteur)>
                            {{ __("secteur.$secteur") }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field" data-champ="region">
                <label for="region">{{ __('admin.qr.region') }}</label>
                <select id="region" name="region" class="form-control">
                    <option value="">{{ __('admin.qr.none') }}</option>
                    @foreach(config('taxonomy.regions') as $region)
                        <option value="{{ $region }}" @selected(old('region') === $region)>
                            {{ __("region.$region") }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="support">{{ __('admin.qr.support') }}</label>
                <input id="support" name="support" class="form-control" value="{{ old('support') }}">
            </div>

            <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('admin.qr.create') }}</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Ce que porte chaque type de QR, selon le §6.1 de la note de cadrage.
    // Doit rester aligné sur les règles de validation du contrôleur.
    var MODELE = {
        NATIONAL:      {etablissement: 'off', secteur: 'off',      region: 'off',      hint: @json(__('admin.qr.hintNATIONAL'))},
        SECTORIEL:     {etablissement: 'off', secteur: 'requis',   region: 'facultatif', hint: @json(__('admin.qr.hintSECTORIEL'))},
        ETABLISSEMENT: {etablissement: 'requis', secteur: 'facultatif', region: 'facultatif', hint: @json(__('admin.qr.hintETABLISSEMENT'))}
    };

    var type = document.getElementById('type');
    var hint = document.getElementById('type-hint');

    function appliquer() {
        var m = MODELE[type.value];
        hint.textContent = m.hint;

        ['etablissement', 'secteur', 'region'].forEach(function (nom) {
            var bloc = document.querySelector('[data-champ="' + nom + '"]');
            var champ = document.getElementById(nom);
            var etoile = bloc.querySelector('.fmdc-req');
            var etat = m[nom];

            champ.disabled = (etat === 'off');
            champ.required = (etat === 'requis');
            bloc.classList.toggle('fmdc-field--off', etat === 'off');
            if (etoile) etoile.hidden = (etat !== 'requis');

            // Un champ désactivé n'est pas envoyé, mais on le vide tout de même :
            // le réactiver ne doit pas ressusciter une valeur choisie par erreur.
            if (etat === 'off') champ.value = '';
        });
    }

    type.addEventListener('change', appliquer);
    appliquer();
})();
</script>
@endpush
