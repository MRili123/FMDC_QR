@php($rtl = $locale === 'ar')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('admin.nav.logout') }} — QR Conso Maroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/fmdc.css') }}">
    @if($rtl)<link rel="stylesheet" href="{{ asset('theme/rtl.css') }}">@endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">
</head>
<body class="fmdc-public">

<div class="container" style="max-width:420px;margin-top:12vh">
    <div class="fmdc-card text-center">
        <i class="las la-sign-out-alt" style="font-size:38px;color:var(--fmdc-primary)"></i>
        <h1 style="font-size:19px;font-weight:700;margin:12px 0 6px">{{ __('admin.logout.confirmTitle') }}</h1>
        <p class="text-muted" style="font-size:14px">
            {{ __('admin.logout.confirmBody', ['name' => auth()->user()->name]) }}
        </p>

        <form method="POST" action="{{ route('admin.logout', $locale) }}">
            @csrf
            <button type="submit" class="fmdc-btn fmdc-btn--block mb-2">{{ __('admin.nav.logout') }}</button>
        </form>

        <a href="{{ route('admin.dossiers.index', $locale) }}" class="fmdc-btn fmdc-btn--ghost fmdc-btn--block">
            {{ __('admin.logout.cancel') }}
        </a>
    </div>
</div>

</body>
</html>
