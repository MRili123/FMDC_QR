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
        <form method="POST" action="{{ route('admin.qrcodes.store', $locale) }}" class="fmdc-stat">
            @csrf
            <h2 style="font-size:15px;font-weight:600;margin-bottom:12px">{{ __('admin.qr.create') }}</h2>

            <div class="fmdc-field">
                <label for="type">{{ __('admin.qr.type') }}</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="NATIONAL">NATIONAL</option>
                    <option value="SECTORIEL">SECTORIEL</option>
                    <option value="ETABLISSEMENT">ETABLISSEMENT</option>
                </select>
            </div>

            <div class="fmdc-field">
                <label for="libelle">{{ __('admin.qr.libelle') }}</label>
                <input id="libelle" name="libelle" class="form-control" required>
            </div>

            <div class="fmdc-field">
                <label for="etablissement">{{ __('admin.qr.etablissement') }}</label>
                <input id="etablissement" name="etablissement" class="form-control">
            </div>

            <div class="fmdc-field">
                <label for="secteur">{{ __('admin.qr.secteur') }}</label>
                <select id="secteur" name="secteur" class="form-control">
                    <option value="">{{ __('admin.qr.none') }}</option>
                    @foreach(config('taxonomy.secteurs') as $secteur)
                        <option value="{{ $secteur }}">{{ __("secteur.$secteur") }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="region">{{ __('admin.qr.region') }}</label>
                <select id="region" name="region" class="form-control">
                    <option value="">{{ __('admin.qr.none') }}</option>
                    @foreach(config('taxonomy.regions') as $region)
                        <option value="{{ $region }}">{{ __("region.$region") }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="support">{{ __('admin.qr.support') }}</label>
                <input id="support" name="support" class="form-control">
            </div>

            <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('admin.qr.create') }}</button>
        </form>
    </div>
</div>
@endsection
