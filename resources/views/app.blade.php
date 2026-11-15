<!DOCTYPE html>
<html lang="en-NG">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>integrator</title>
        <meta name="description" content="Built by an integrator for integrators in Nigeria.">
        {{-- Installable: the web manifest (display standalone), the green top bar's colour, and iOS's own tags. --}}
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#153d2e">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="integrator">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <link rel="icon" href="/icons/icon-192.png" type="image/png">
        @viteReactRefresh
        @vite('resources/app/main.tsx')
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
