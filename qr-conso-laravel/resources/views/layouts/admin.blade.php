@php($locale = app()->getLocale())
@php($rtl = $locale === 'ar')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Back-office')) — QR Conso Maroc</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/bootstrap/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/assets/vendor_assets/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/style.css') }}">
    <link rel="stylesheet" href="{{ asset('theme/fmdc.css') }}">
    @if($rtl)
        <link rel="stylesheet" href="{{ asset('theme/rtl.css') }}">
    @endif
    <link rel="icon" type="image/svg+xml" href="{{ asset('icon.svg') }}">
</head>
<body class="layout-light side-menu overlayScroll">

<header class="header-top">
    <nav class="navbar navbar-light">
        <div class="navbar-left">
            <a href="#" class="sidebar-toggle"><i class="las la-bars fs-24"></i></a>
            <a class="navbar-brand" href="{{ route('admin.dossiers.index', $locale) }}">
                <span class="fmdc-brand">QR Conso <strong>Maroc</strong></span>
            </a>
        </div>
        <div class="navbar-right">
            <ul class="navbar-right__menu">
                <li class="nav-author">
                    <span class="fmdc-user">
                        {{ auth()->user()->name }}
                        <small class="d-block text-light">
                            {{ auth()->user()->isFmdcAdmin()
                                ? __('Bureau national')
                                : (auth()->user()->association?->nom ?? __('Association')) }}
                        </small>
                    </span>
                </li>
                <li>
                    <a href="{{ route('admin.dossiers.index', $rtl ? 'fr' : 'ar') }}" class="btn btn-sm btn-outline-light ml-2">
                        {{ $rtl ? 'Français' : 'العربية' }}
                    </a>
                </li>
                <li>
                    <form method="POST" action="{{ route('admin.logout', $locale) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger ml-2">{{ __('Se déconnecter') }}</button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>
</header>

<main class="main-content">
    <aside class="sidebar-wrapper">
        <div class="sidebar sidebar-collapse" id="sidebar">
            <div class="sidebar__menu-group">
                <ul class="sidebar_nav">
                    <li class="menu-title"><span>{{ __('Pilotage') }}</span></li>

                    <li class="{{ request()->routeIs('admin.dossiers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.dossiers.index', $locale) }}">
                            <i class="las la-folder-open nav-icon"></i>
                            <span class="menu-text">{{ __('Dossiers') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard', $locale) }}">
                            <i class="las la-chart-bar nav-icon"></i>
                            <span class="menu-text">{{ __('Tableau de bord') }}</span>
                        </a>
                    </li>

                    <li class="menu-title"><span>{{ __('Configuration') }}</span></li>

                    <li class="{{ request()->routeIs('admin.qrcodes.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.qrcodes.index', $locale) }}">
                            <i class="las la-qrcode nav-icon"></i>
                            <span class="menu-text">{{ __('QR codes') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.associations.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.associations.index', $locale) }}">
                            <i class="las la-users nav-icon"></i>
                            <span class="menu-text">{{ __('Associations') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <div class="contents">
        <div class="atbd-page-content">
            <div class="container-fluid">
                @if(session('status'))
                    <div class="alert alert-success mt-20">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
</main>

<script src="{{ asset('theme/assets/vendor_assets/js/jquery/jquery-3.5.1.min.js') }}"></script>
<script src="{{ asset('theme/assets/vendor_assets/js/bootstrap/popper.js') }}"></script>
<script src="{{ asset('theme/assets/vendor_assets/js/bootstrap/bootstrap.min.js') }}"></script>
<script src="{{ asset('theme/assets/vendor_assets/js/Chart.min.js') }}"></script>
<script>
    // La sidebar du thème dépend de scripts que nous ne chargeons pas (cartes,
    // calendrier, éditeurs) : ce repli suffit pour le seul comportement utile ici.
    $('.sidebar-toggle').on('click', function (e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-hide');
    });
</script>
@stack('scripts')
</body>
</html>
