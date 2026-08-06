@php($rtl = $locale === 'ar')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.login.title') }} — QR Conso Maroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/fmdc.css') }}">
    @if($rtl)<link rel="stylesheet" href="{{ asset('theme/rtl.css') }}">@endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">
</head>
<body class="fmdc-public">

<div class="container" style="max-width:420px;margin-top:8vh">

    <div class="text-center mb-4">
        <span class="fmdc-logo__mark" style="font-size:15px;padding:7px 11px">FMDC</span>
        <h1 style="font-size:20px;font-weight:700;margin-top:14px">{{ __('admin.login.title') }}</h1>
    </div>

    <form method="POST" action="{{ route('admin.login.attempt', $locale) }}" class="fmdc-card">
        @csrf

        @if($errors->any())
            <div class="alert alert-danger py-2">
                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
            </div>
        @endif

        <div class="fmdc-field">
            <label for="email">{{ __('admin.login.email') }}</label>
            <input id="email" name="email" type="email" class="form-control" required autofocus
                   value="{{ old('email') }}">
        </div>

        <div class="fmdc-field">
            <label for="password">{{ __('admin.login.password') }}</label>
            <input id="password" name="password" type="password" class="form-control" required>
        </div>

        <label class="d-flex align-items-center mb-3" style="gap:8px;font-size:14px;cursor:pointer">
            <input type="checkbox" name="remember" value="1"> {{ __('admin.login.remember') }}
        </label>

        <button type="submit" class="fmdc-btn fmdc-btn--block">{{ __('admin.login.submit') }}</button>
    </form>

    <p class="text-center">
        <a href="{{ route('home', $locale) }}" style="font-size:14px;color:var(--fmdc-muted)">
            {{ __('nav.home') }}
        </a>
    </p>

</div>

</body>
</html>
