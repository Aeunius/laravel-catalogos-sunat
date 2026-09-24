<?php

// Revisa los datos generados, no el código: que ningún catálogo llegue vacío,
// sin nombre o con descripciones en blanco, y que las propiedades tipadas
// tengan el tipo que promete FUENTES.md.

const DIRECTORIO = __DIR__.'/../../resources/catalogos';

dataset('catalogos', function () {
    foreach (glob(DIRECTORIO.'/*.json') as $archivo) {
        yield basename($archivo, '.json') => [json_decode(file_get_contents($archivo), true, flags: JSON_THROW_ON_ERROR)];
    }
});

it('tiene nombre, fuente y códigos', function (array $catalogo) {
    expect($catalogo['catalogo'])->toBeString()->not->toBeEmpty()
        ->and($catalogo['nombre'])->toBeString()->not->toBeEmpty()
        ->and($catalogo['fuente'])->toBeString()->not->toBeEmpty()
        ->and($catalogo['items'])->toBeArray()->not->toBeEmpty();
})->with('catalogos');

it('tiene descripción en cada código', function (array $catalogo) {
    foreach ($catalogo['items'] as $codigo => $item) {
        expect($item['descripcion'] ?? null)
            ->toBeString("El código {$codigo} no tiene descripción")
            ->not->toBeEmpty();
    }
})->with('catalogos');

it('no deja espacios sobrantes', function (array $catalogo) {
    foreach ($catalogo['items'] as $codigo => $item) {
        foreach ($item as $valor) {
            if (is_string($valor)) {
                expect($valor)->toBe(trim(preg_replace('/\s+/u', ' ', $valor)), "Código {$codigo}: [{$valor}]");
            }
        }
    }
})->with('catalogos');

it('incluye todos los catálogos del Anexo N.° 8', function () {
    $numeros = array_map(fn ($a) => basename($a, '.json'), glob(DIRECTORIO.'/*.json'));

    $esperados = [
        ...array_map(fn ($n) => sprintf('%02d', $n), range(1, 27)),
        ...array_map('strval', range(51, 65)),
        'D-37', '25-jerarquia',
    ];

    expect($numeros)->toEqualCanonicalizing($esperados);
});

it('tipa las propiedades adicionales', function () {
    $leer = fn (string $n) => json_decode(file_get_contents(DIRECTORIO."/{$n}.json"), true)['items'];

    foreach ($leer('07') as $item) {
        expect($item['tributos'])->toBeList()->each->toMatch('/^\d{4}$/');
    }
    foreach ([...$leer('22'), ...$leer('23')] as $item) {
        expect($item['porcentaje'])->toBeNumeric()->not->toBeString();
    }
    foreach ($leer('51') as $item) {
        expect($item['comprobantes'])->toBeList()->each->toBeIn(['factura', 'boleta', 'liquidacion_compra']);
    }
    foreach ($leer('53') as $item) {
        expect($item['nivel'])->toBeIn(['item', 'global']);
    }
    foreach ($leer('61') as $item) {
        expect($item['gre'])->toBeList()->each->toBeIn(['remitente', 'transportista']);
    }
    foreach ($leer('03') as $item) {
        expect($item['estado'])->toBeIn(['vigente', 'obsoleto', 'eliminado']);
    }
});

it('tiene los códigos que más se usan al facturar', function (string $numero, string $codigo) {
    $items = json_decode(file_get_contents(DIRECTORIO."/{$numero}.json"), true)['items'];

    expect($items)->toHaveKey($codigo);
})->with([
    'factura' => ['01', '01'],
    'boleta' => ['01', '03'],
    'soles' => ['02', 'PEN'],
    'dólares' => ['02', 'USD'],
    'unidad (bienes)' => ['03', 'NIU'],
    'unidad (servicios)' => ['03', 'ZZ'],
    'IGV' => ['05', '1000'],
    'DNI' => ['06', '1'],
    'RUC' => ['06', '6'],
    'gravado' => ['07', '10'],
    'venta interna' => ['51', '0101'],
    'percepción' => ['51', '2001'],
    'descuento global' => ['53', '02'],
    'monto en letras' => ['52', '1000'],
]);
