<?php

use Aeunius\CatalogosSunat\Catalogos;
use Aeunius\CatalogosSunat\Enums\Moneda;
use Aeunius\CatalogosSunat\Enums\TipoAfectacionIgv;
use Aeunius\CatalogosSunat\Enums\TipoDocumento;
use Aeunius\CatalogosSunat\Enums\TipoDocumentoIdentidad;
use Aeunius\CatalogosSunat\Item;

dataset('enums', [
    '01' => TipoDocumento::class,
    '02' => Moneda::class,
    '06' => TipoDocumentoIdentidad::class,
    '07' => TipoAfectacionIgv::class,
]);

// Si la SUNAT agrega o retira un código, `make catalogos` lo refleja en el JSON
// y este test señala el enum que hay que actualizar.
it('tiene un caso por cada código del catálogo', function (string $enum) {
    $valores = array_map(fn (BackedEnum $caso) => $caso->value, $enum::cases());

    expect($valores)->toEqualCanonicalizing((new Catalogos)->get($enum::catalogo())->codigos());
})->with('enums');

it('toma la descripción y las propiedades del catálogo', function (string $enum) {
    foreach ($enum::cases() as $caso) {
        expect($caso->item())->toBeInstanceOf(Item::class)
            ->and($caso->item()->codigo)->toBe($caso->value)
            ->and($caso->descripcion())->toBe((new Catalogos)->descripcion($enum::catalogo(), $caso->value));
    }
})->with('enums');

it('usa los códigos de la SUNAT como valor', function () {
    expect(TipoDocumento::Factura->value)->toBe('01')
        ->and(TipoDocumento::from('03'))->toBe(TipoDocumento::BoletaVenta)
        ->and(TipoDocumento::tryFrom('02'))->toBeNull()
        ->and(TipoDocumentoIdentidad::Ruc->value)->toBe('6')
        ->and(TipoAfectacionIgv::GravadoOneroso->value)->toBe('10')
        ->and(Moneda::PEN->value)->toBe('PEN');
});

it('describe cada caso', function () {
    expect(TipoDocumento::Factura->descripcion())->toBe('Factura')
        ->and(TipoDocumentoIdentidad::Dni->descripcion())->toBe('Documento Nacional de Identidad')
        ->and(TipoAfectacionIgv::Exportacion->descripcion())->toBe('Exportación de Bienes o Servicios')
        ->and(Moneda::PEN->descripcion())->toBe('sol peruano');
});

it('distingue notas y guías de remisión', function () {
    expect(TipoDocumento::NotaCredito->esNota())->toBeTrue()
        ->and(TipoDocumento::NotaDebitoEspecial->esNota())->toBeTrue()
        ->and(TipoDocumento::Factura->esNota())->toBeFalse()
        ->and(TipoDocumento::GuiaRemisionTransportista->esGuiaRemision())->toBeTrue()
        ->and(TipoDocumento::BoletaVenta->esGuiaRemision())->toBeFalse();
});

it('agrupa las afectaciones del IGV', function () {
    expect(TipoAfectacionIgv::GravadoRetiroPremio->esGravado())->toBeTrue()
        ->and(TipoAfectacionIgv::ExoneradoOneroso->esExonerado())->toBeTrue()
        ->and(TipoAfectacionIgv::InafectoRetiro->esInafecto())->toBeTrue()
        ->and(TipoAfectacionIgv::Exportacion->esExportacion())->toBeTrue()
        ->and(TipoAfectacionIgv::GravadoOneroso->esExonerado())->toBeFalse();

    foreach (TipoAfectacionIgv::cases() as $caso) {
        $grupos = [$caso->esGravado(), $caso->esExonerado(), $caso->esInafecto(), $caso->esExportacion()];
        expect(array_sum($grupos))->toBe(1, "{$caso->name} debe estar en un solo grupo");
    }
});

it('indica los tributos que admite cada afectación', function () {
    expect(TipoAfectacionIgv::GravadoOneroso->tributos())->toBe(['1000'])
        ->and(TipoAfectacionIgv::GravadoIvap->tributos())->toBe(['1016', '9996'])
        ->and(TipoAfectacionIgv::InafectoTransferenciaGratuita->tributos())->toBe(['9996']);
});

it('da el símbolo y los decimales de la moneda', function () {
    expect(Moneda::PEN->simbolo())->toBe('S/')
        ->and(Moneda::USD->simbolo())->toBe('$')
        ->and(Moneda::PEN->decimales())->toBe(2)
        ->and(Moneda::JPY->decimales())->toBe(0)
        ->and(Moneda::XAU->decimales())->toBeNull();
});
