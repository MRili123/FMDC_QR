@php($locale = app()->getLocale())
@php($rtl = $locale === 'ar')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b4d8f">
    <title>@yield('title', 'QR Conso Maroc') — FMDC</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/fmdc.css') }}">
    @if($rtl)
        <link rel="stylesheet" href="{{ asset('theme/rtl.css') }}">
    @endif
    <link rel="manifest" href="{{ route('manifest', $locale) }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">
</head>
<body class="fmdc-public">

<header class="fmdc-header">
    <div class="fmdc-header__inner">
        <a href="{{ route('home', $locale) }}" class="fmdc-logo">
            <span class="fmdc-logo__mark">FMDC</span>
            <span class="fmdc-logo__text">{{ __('app.name') }}</span>
        </a>
        <nav class="fmdc-header__nav">
            <a href="{{ route('suivi.form', $locale) }}">{{ __('nav.track') }}</a>
            <a href="{{ route(Route::currentRouteName() ?: 'home', array_merge(request()->route()->parameters(), ['locale' => $rtl ? 'fr' : 'ar'])) }}"
               class="fmdc-lang">{{ $rtl ? 'Français' : 'العربية' }}</a>
        </nav>
    </div>
</header>

<main class="fmdc-main">
    @if(session('status'))
        <div class="container"><div class="alert alert-success">{{ session('status') }}</div></div>
    @endif
    @yield('content')
</main>

<footer class="fmdc-footer">
    <div class="container">
        <p class="mb-1">{{ __('app.org') }}</p>
        <p class="fmdc-footer__warn">
            <i class="las la-shield-alt"></i>
            {{ __('qr.neverBank') }}
        </p>
    </div>
</footer>

<script src="{{ asset('theme/assets/vendor_assets/js/jquery/jquery-3.5.1.min.js') }}"></script>
@stack('scripts')
</body>
</html>
