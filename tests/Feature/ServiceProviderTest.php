<?php

use Aeunius\CatalogosSunat\Catalogos;
use Aeunius\CatalogosSunat\CatalogosSunatServiceProvider;
use Aeunius\CatalogosSunat\Facades\Catalogos as CatalogosFacade;

it('registra el service provider', function () {
    expect(app()->getProviders(CatalogosSunatServiceProvider::class))->not->toBeEmpty();
});

it('registra Catalogos como singleton', function () {
    expect(app(Catalogos::class))->toBe(app(Catalogos::class));
});

it('resuelve el facade a la misma instancia', function () {
    expect(CatalogosFacade::getFacadeRoot())->toBe(app(Catalogos::class));
});
