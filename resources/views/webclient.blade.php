@php
    // The foundation registry is the only allowlist. Never select a catalog from
    // an untrusted path or maintain another supported-language list here.
    $rdLocales = config('locales.supported', []);
    $rdFallbackLocale = 'en';
    $rdRequestedLocale = str_replace('_', '-', strtolower((string) app()->getLocale()));
    $rdLocale = array_key_exists($rdRequestedLocale, $rdLocales) ? $rdRequestedLocale : null;
    if ($rdLocale === null) {
        foreach ($rdLocales as $code => $metadata) {
            $aliases = array_map(fn ($alias) => strtolower(str_replace('_', '-', $alias)), $metadata['aliases'] ?? []);
            if (in_array($rdRequestedLocale, $aliases, true) || str_starts_with($rdRequestedLocale, strtolower($code).'-')) {
                $rdLocale = $code;
                break;
            }
        }
    }
    $rdLocale = $rdLocale ?? $rdFallbackLocale;
    $rdMeta = $rdLocales[$rdLocale] ?? $rdLocales[$rdFallbackLocale] ?? [];
    $loadWebclientCatalog = static function (string $locale): array {
        $path = lang_path('webclient/'.$locale.'.json');
        if (!is_file($path)) return [];
        $decoded = json_decode((string) file_get_contents($path), true);
        return is_array($decoded) ? $decoded : [];
    };
    $rdCatalog = $loadWebclientCatalog($rdLocale);
    $rdFallbackCatalog = $loadWebclientCatalog($rdFallbackLocale);
    $rdVer = @filemtime(public_path('rdclient/app.js')) ?: time();
@endphp
<!DOCTYPE html>
<html lang="{{ $rdMeta['html_lang'] ?? $rdLocale }}" dir="{{ $rdMeta['dir'] ?? 'ltr' }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $peerId !== '' ? $peerId.' — ' : '' }}{{ $rdCatalog['app.title'] ?? $rdFallbackCatalog['app.title'] ?? 'CortenDesk Web Client' }}</title>
    <link rel="shortcut icon" href="{{ \App\Support\Asset::url('assets/images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ \App\Support\Asset::url('assets/css/icons.min.css') }}">
    <link rel="stylesheet" href="/rdclient/app.css?v={{ $rdVer }}">
    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; background: #000; }
        #rd-root { height: 100vh; height: 100dvh; }
    </style>
</head>
<body>
    <div id="rd-root">
        <div id="rd-toolbar"></div>
        <div id="rd-viewport">
            <canvas id="rd-canvas"></canvas>
            <div id="rd-stats"></div>
            <div id="rd-overlay"></div>
        </div>
    </div>
    <script>
        window.__RD__ = {
            peerId: @json($peerId),
            serverKeyB64: @json($serverKeyB64),
            wsIdUrl: @json($wsIdUrl),
            wsRelayUrl: @json($wsRelayUrl),
            myId: @json($myId),
            myName: @json($myName),
            version: @json(config('cortendesk.api_version')),
            workerUrl: '/rdclient/session.worker.js?v={{ $rdVer }}',
            i18n: {
                locale: @json($rdLocale),
                catalog: @json($rdCatalog),
                fallbackCatalog: @json($rdFallbackCatalog)
            }
        };
    </script>
    <script type="module" src="/rdclient/app.js?v={{ $rdVer }}" data-rd-worker="/rdclient/session.worker.js?v={{ $rdVer }}"></script>
</body>
</html>
