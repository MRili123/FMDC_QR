@php($locale = app()->getLocale())
@php($rtl = $locale === 'ar')
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('nav.admin')) — QR Conso Maroc</title>

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
{{-- Pas de classe `overlayScroll` : elle met `overflow: hidden` sur le body et
     confie le défilement au script de barre de défilement personnalisée du
     thème, que nous ne chargeons pas. La page devenait alors impossible à faire
     défiler. --}}
<body class="layout-light side-menu">

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
                                ? __('track.assignedNational')
                                : (auth()->user()->association?->nom ?? __('admin.associations.title')) }}
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
                        <button type="submit" class="btn btn-sm btn-outline-danger ml-2">{{ __('admin.nav.logout') }}</button>
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
                    <li class="menu-title"><span>{{ __('admin.nav.sectionPilotage') }}</span></li>

                    <li class="{{ request()->routeIs('admin.dossiers.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.dossiers.index', $locale) }}">
                            <i class="las la-folder-open nav-icon"></i>
                            <span class="menu-text">{{ __('admin.nav.queue') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard', $locale) }}">
                            <i class="las la-chart-bar nav-icon"></i>
                            <span class="menu-text">{{ __('admin.nav.dashboard') }}</span>
                        </a>
                    </li>

                    <li class="menu-title"><span>{{ __('admin.nav.sectionConfig') }}</span></li>

                    <li class="{{ request()->routeIs('admin.qrcodes.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.qrcodes.index', $locale) }}">
                            <i class="las la-qrcode nav-icon"></i>
                            <span class="menu-text">{{ __('admin.nav.qr') }}</span>
                        </a>
                    </li>
                    <li class="{{ request()->routeIs('admin.associations.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.associations.index', $locale) }}">
                            <i class="las la-users nav-icon"></i>
                            <span class="menu-text">{{ __('admin.nav.associations') }}</span>
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
    // Le script de barre latérale du thème n'est pas chargé (il dépend de
    // cartes, calendriers et éditeurs dont nous n'avons pas l'usage). Ce repli
    // couvre les deux comportements utiles, selon la largeur de la fenêtre :
    // replier la barre sur grand écran, l'ouvrir en tiroir en dessous de 1150px.
    var LARGE = window.matchMedia('(min-width: 1151px)');

    $('.sidebar-toggle').on('click', function (e) {
        e.preventDefault();
        $('body').toggleClass(LARGE.matches ? 'sidebar-hide' : 'sidebar-open');
    });

    // Refermer le tiroir en touchant le voile, ou en suivant un lien du menu.
    $(document).on('click', function (e) {
        if (! LARGE.matches && $('body').hasClass('sidebar-open')
            && ! $(e.target).closest('.sidebar-wrapper, .sidebar-toggle').length) {
            $('body').removeClass('sidebar-open');
        }
    });

    // Un changement de largeur ne doit pas laisser la page dans un état hybride.
    LARGE.addEventListener('change', function () {
        $('body').removeClass('sidebar-open sidebar-hide');
    });
</script>
@stack('scripts')
</body>
</html>
