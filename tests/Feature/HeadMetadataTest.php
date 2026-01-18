<?php

test('head uses the app icon favicon', function () {
    $html = view('partials.head')->render();

    expect($html)
        ->toContain('rel="icon"')
        ->toContain('href="/favicon.svg"')
        ->not->toContain('favicon.ico');
});
