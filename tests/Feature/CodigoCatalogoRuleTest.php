<?php

use Aeunius\CatalogosSunat\Enums\Moneda;
use Aeunius\CatalogosSunat\Exceptions\CatalogoNoExiste;
use Aeunius\CatalogosSunat\Rules\CodigoCatalogo;
use Illuminate\Support\Facades\Validator;

function validaCodigo(mixed $valor, mixed $regla): Illuminate\Validation\Validator
{
    return Validator::make(['codigo' => $valor], ['codigo' => [$regla]]);
}

it('acepta un código del catálogo', function (string $catalogo, mixed $codigo) {
    expect(validaCodigo($codigo, new CodigoCatalogo($catalogo))->passes())->toBeTrue()
        ->and(validaCodigo($codigo, "codigo_catalogo:{$catalogo}")->passes())->toBeTrue();
})->with([
    'factura' => ['01', '01'],
    'soles' => ['02', 'PEN'],
    'RUC' => ['06', '6'],
    'RUC como entero' => ['06', 6],
    'catálogo sin cero' => ['6', '6'],
    'unidad retirada, que la SUNAT aún acepta' => ['03', '05'],
    'listado de la GRE' => ['D-37', '04'],
]);

it('rechaza lo que no es un código del catálogo', function (mixed $codigo) {
    expect(validaCodigo($codigo, new CodigoCatalogo('01'))->fails())->toBeTrue()
        ->and(validaCodigo($codigo, 'codigo_catalogo:01')->fails())->toBeTrue();
})->with([
    'no existe' => '02',
    'sin cero a la izquierda' => '1',
    'con espacios' => ' 01',
    'arreglo' => [['01']],
    'decimal' => 1.0,
    'booleano' => true,
]);

it('acepta un enum como valor', function () {
    expect(validaCodigo(Moneda::PEN, new CodigoCatalogo('02'))->passes())->toBeTrue();
});

it('exige la propiedad con donde()', function () {
    $boleta = fn () => (new CodigoCatalogo('51'))->donde('comprobantes', 'boleta');

    expect(validaCodigo('0101', $boleta())->passes())->toBeTrue()
        // 0112 existe, pero solo para factura.
        ->and(validaCodigo('0112', $boleta())->fails())->toBeTrue()
        ->and(validaCodigo('9999', $boleta())->fails())->toBeTrue()
        ->and(validaCodigo('02', (new CodigoCatalogo('53'))->donde('nivel', 'global'))->passes())->toBeTrue()
        ->and(validaCodigo('00', (new CodigoCatalogo('53'))->donde('nivel', 'global'))->fails())->toBeTrue()
        ->and(validaCodigo('01', (new CodigoCatalogo('22'))->donde('porcentaje', 2))->passes())->toBeTrue();
});

it('compara los números por su valor en donde()', function () {
    $percepcion = fn (int|float|string $porcentaje) => (new CodigoCatalogo('22'))->donde('porcentaje', $porcentaje);

    expect(validaCodigo('01', $percepcion(2.0))->passes())->toBeTrue()
        ->and(validaCodigo('03', $percepcion(0.5))->passes())->toBeTrue()
        // Un texto no es un número: "2" no es igual a 2.
        ->and(validaCodigo('01', $percepcion('2'))->fails())->toBeTrue();
});

it('muestra el mensaje traducido', function (string $locale, string $mensaje) {
    app()->setLocale($locale);

    expect(validaCodigo('XYZ', new CodigoCatalogo('02'))->errors()->first('codigo'))->toBe($mensaje)
        ->and(validaCodigo('XYZ', 'codigo_catalogo:02')->errors()->first('codigo'))->toBe($mensaje);
})->with([
    ['es', 'El campo codigo debe ser un código del catálogo 02 de la SUNAT (Código de tipo de monedas).'],
    ['en', 'The codigo field must be a code from SUNAT catalog 02 (Código de tipo de monedas).'],
]);

it('explica qué condición no cumple el código', function () {
    app()->setLocale('es');

    expect(validaCodigo('0112', (new CodigoCatalogo('51'))->donde('comprobantes', 'boleta'))->errors()->first('codigo'))
        ->toBe('El campo codigo debe ser un código del catálogo 51 de la SUNAT con comprobantes: boleta.');
});

it('falla al construir la regla si el catálogo no existe', function () {
    new CodigoCatalogo('99');
})->throws(CatalogoNoExiste::class);

it('exige el número de catálogo en la regla en texto', function () {
    validaCodigo('01', 'codigo_catalogo')->passes();
})->throws(InvalidArgumentException::class, 'codigo_catalogo:02');
