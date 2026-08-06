<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $qr->libelle }} — QR Conso Maroc</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Inter, system-ui, sans-serif; margin: 0; background: #eef1f6; }
        .poster {
            width: 148mm; min-height: 210mm; margin: 20px auto; background: #fff; padding: 18mm 14mm;
            box-sizing: border-box; text-align: center; display: flex; flex-direction: column;
        }
        .brand { color: #1b4d8f; font-weight: 700; font-size: 15pt; letter-spacing: .5px; }
        .org { color: #667085; font-size: 8.5pt; margin-top: 3mm; line-height: 1.4; }
        .headline { font-size: 20pt; font-weight: 700; color: #1c2431; margin: 10mm 0 3mm; line-height: 1.2; }
        .sub { font-size: 11pt; color: #667085; margin-bottom: 8mm; }
        .code { border: 2px solid #1b4d8f; border-radius: 8px; padding: 6mm; display: inline-block; }
        .code svg { display: block; width: 62mm; height: 62mm; }
        /* L'adresse imprimée en clair est la première défense contre le quishing (§9). */
        .host { font-family: ui-monospace, monospace; font-size: 11pt; font-weight: 600;
                color: #1b4d8f; margin-top: 5mm; direction: ltr; }
        .etab { margin-top: 7mm; font-size: 11pt; font-weight: 600; }
        .etab small { display: block; font-weight: 400; color: #667085; font-size: 9pt; margin-top: 1mm; }
        .warn { margin-top: auto; padding-top: 8mm; color: #c8102e; font-size: 9.5pt; font-weight: 600; }
        .ref { color: #98a2b3; font-size: 7.5pt; margin-top: 4mm; direction: ltr; }
        .toolbar { text-align: center; margin: 16px; }
        .toolbar button { padding: 10px 20px; font-size: 14px; border-radius: 8px;
                          border: none; background: #1b4d8f; color: #fff; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .poster { margin: 0; width: auto; min-height: auto; }
            @page { size: A5; margin: 0; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <button onclick="window.print()">{{ __('admin.qr.download') }}</button>
</div>

<div class="poster">
    <div class="brand">FMDC</div>
    <div class="org">{{ __('app.org') }}</div>

    <div class="headline">{{ __('qr.posterHeadline') }}</div>
    <div class="sub">{{ __('qr.posterSub') }}</div>

    <div><span class="code">{!! $svg !!}</span></div>

    <div class="host">{{ $printedHost }}</div>

    @if($qr->etablissement)
        <div class="etab">
            {{ $qr->etablissement }}
            @if($qr->region)<small>{{ __("region.{$qr->region}") }}</small>@endif
        </div>
    @endif

    <div class="warn">{{ __('qr.neverBank') }}</div>
    <div class="ref">{{ $qr->code }} · {{ $qr->support }}</div>
</div>

</body>
</html>
