<?php

/*
 * The Integrator app shell (Story 1.7): one view for every app path, with the
 * tags that make it installable and open without an address bar.
 */

it('answers every app path, unknown ones too, with the shell and HTTP 200', function (string $path) {
    $this->withoutVite()
        ->get($path)
        ->assertOk()
        ->assertSee('<div id="app"></div>', false);
})->with(['/', '/install', '/accessibility', '/signed-out', '/nothing-here', '/deep/unknown/path']);

it('carries the tags that make it installable and open full screen on Android and iPhone', function () {
    $html = $this->withoutVite()->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<html lang="en-NG">')
        ->toContain('viewport-fit=cover')
        ->toContain('<link rel="manifest" href="/build/manifest.webmanifest">')
        ->toContain('<meta name="theme-color" content="#153d2e">')
        ->toContain('<meta name="apple-mobile-web-app-capable" content="yes">')
        ->toContain('<meta name="apple-mobile-web-app-title" content="integrator">')
        ->toContain('<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">');
});

it('ships the icons the manifest names, a maskable one among them', function () {
    foreach (['icon-192.png', 'icon-512.png', 'maskable-512.png', 'apple-touch-icon.png'] as $icon) {
        expect(public_path("icons/{$icon}"))->toBeFile();
    }

    $config = (string) file_get_contents(base_path('vite.config.ts'));

    expect($config)
        ->toContain("display: 'standalone'")
        ->toContain("purpose: 'maskable'")
        ->toContain('navigateFallbackDenylist: NEVER_ANSWERED')
        ->toContain('/^\/api(\/|$)/,')
        ->toContain('/^\/console(\/|$)/,')
        ->toContain('/^\/up$/,')
        ->toContain('/^\/filament(\/|$)/,')
        ->toContain('/^\/livewire(-[0-9a-f]+)?(\/|$)/,')
        ->toContain('/^\/storage(\/|$)/,')
        ->toContain('/^\/sanctum(\/|$)/,')
        ->toContain('/\.[a-z0-9]+$/i,');
});

it('never serves the shell for /api, /console, /up or the vendor routes beside them', function () {
    $this->withoutVite();

    expect($this->getJson('/api/v1/health')->assertOk()->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/api/v1/nothing')->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/up')->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/console/login')->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/filament/exports/1/download')->getContent())->not->toContain('<div id="app">')
        ->and($this->get(route('livewire.preview-file', ['filename' => 'nothing.png'], false))->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/storage/nothing.png')->getContent())->not->toContain('<div id="app">')
        ->and($this->get('/sanctum/csrf-cookie')->getContent())->not->toContain('<div id="app">');
});
