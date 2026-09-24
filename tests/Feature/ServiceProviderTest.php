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

it('consulta los catálogos desde el facade', function () {
    expect(CatalogosFacade::descripcion('01', '01'))->toBe('Factura')
        ->and(CatalogosFacade::existe('06', '6'))->toBeTrue()
        ->and(CatalogosFacade::get('02')->buscar('PEN')?->get('simbolo'))->toBe('S/');
});
