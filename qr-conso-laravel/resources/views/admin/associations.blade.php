@extends('layouts.admin')
@section('title', __('admin.associations.title'))

@section('content')
<h1 class="fmdc-page-title">{{ __('admin.associations.title') }}</h1>

<div class="row">
    <div class="col-lg-7">
        @foreach($associations as $association)
            <div class="fmdc-stat mb-3">
                <h2 style="font-size:16px;font-weight:600;margin-bottom:2px">{{ $association->nom }}</h2>
                <p class="text-muted mb-2" style="font-size:13px">{{ $association->contact }}</p>

                <div class="mb-3">
                    @foreach($association->scopes as $scope)
                        <span class="fmdc-status mr-1 mb-1 d-inline-block"
                              style="{{ $scope->kind === 'SECTEUR' ? 'background:var(--fmdc-line);color:var(--fmdc-muted)' : '' }}">
                            {{ $scope->kind === 'REGION' ? __("region.{$scope->value}") : __("secteur.{$scope->value}") }}
                        </span>
                    @endforeach
                </div>

                <div class="text-muted mb-1" style="font-size:13px">{{ __('admin.associations.rules') }}</div>
                @foreach($association->rules as $rule)
                    <div class="d-flex align-items-center justify-content-between py-1" style="font-size:14px">
                        <span>
                            {{ __("categorie.{$rule->categorie}") }}
                            <small class="text-muted">
                                — {{ $rule->region ? __("region.{$rule->region}") : __('admin.associations.allRegions') }}
                            </small>
                        </span>
                        <form method="POST" action="{{ route('admin.associations.rules.destroy', [$locale, $rule]) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-link text-danger p-0">{{ __('admin.associations.delete') }}</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="col-lg-5">
        <form method="POST" action="{{ route('admin.associations.store', $locale) }}" class="fmdc-stat mb-3">
            @csrf
            <h2 style="font-size:15px;font-weight:600;margin-bottom:12px">{{ __('admin.associations.create') }}</h2>

            <div class="fmdc-field">
                <label for="nom">{{ __('admin.associations.nom') }}</label>
                <input id="nom" name="nom" class="form-control" required>
            </div>
            <div class="fmdc-field">
                <label for="contact">{{ __('admin.associations.contact') }}</label>
                <input id="contact" name="contact" class="form-control">
            </div>

            <div class="fmdc-field">
                <label>{{ __('admin.associations.regions') }}</label>
                <div style="max-height:150px;overflow-y:auto">
                    @foreach(config('taxonomy.regions') as $region)
                        <label class="d-block mb-1" style="font-size:13px;font-weight:400">
                            <input type="checkbox" name="regions[]" value="{{ $region }}"> {{ __("region.$region") }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="fmdc-field">
                <label>{{ __('admin.associations.secteurs') }}</label>
                <div style="max-height:150px;overflow-y:auto">
                    @foreach(config('taxonomy.secteurs') as $secteur)
                        <label class="d-block mb-1" style="font-size:13px;font-weight:400">
                            <input type="checkbox" name="secteurs[]" value="{{ $secteur }}"> {{ __("secteur.$secteur") }}
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('admin.associations.create') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.associations.rules.store', $locale) }}" class="fmdc-stat">
            @csrf
            <h2 style="font-size:15px;font-weight:600;margin-bottom:12px">{{ __('admin.associations.addRule') }}</h2>

            <div class="fmdc-field">
                <label for="rule-association">{{ __('admin.associations.title') }}</label>
                <select id="rule-association" name="association_id" class="form-control" required>
                    @foreach($associations as $association)
                        <option value="{{ $association->id }}">{{ $association->nom }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="rule-categorie">{{ __('admin.associations.ruleCategory') }}</label>
                <select id="rule-categorie" name="categorie" class="form-control" required>
                    @foreach(config('taxonomy.categories') as $categorie)
                        <option value="{{ $categorie }}">{{ __("categorie.$categorie") }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fmdc-field">
                <label for="rule-region">{{ __('admin.associations.ruleRegion') }}</label>
                <select id="rule-region" name="region" class="form-control">
                    <option value="">{{ __('admin.associations.allRegions') }}</option>
                    @foreach(config('taxonomy.regions') as $region)
                        <option value="{{ $region }}">{{ __("region.$region") }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">{{ __('admin.associations.addRule') }}</button>
        </form>
    </div>
</div>
@endsection
