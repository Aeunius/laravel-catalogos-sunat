<?php

use Aeunius\CatalogosSunat\Catalogo;
use Aeunius\CatalogosSunat\Catalogos;
use Aeunius\CatalogosSunat\Exceptions\CatalogoNoExiste;
use Aeunius\CatalogosSunat\Item;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->catalogos = new Catalogos;
});

it('devuelve un catálogo con su nombre y fuente', function () {
    $catalogo = $this->catalogos->get('01');

    expect($catalogo)->toBeInstanceOf(Catalogo::class)
        ->and($catalogo->numero)->toBe('01')
        ->and($catalogo->nombre)->toBe('Código de tipo de documento')
        ->and($catalogo->fuente)->toContain('SUNAT');
});

it('acepta el número sin cero a la izquierda y como entero', function (string|int $numero) {
    expect($this->catalogos->get($numero)->numero)->toBe('06');
})->with(['6', 6, '06', ' 06 ']);

it('acepta el listado D-37 sin importar mayúsculas', function () {
    expect($this->catalogos->get('d-37')->numero)->toBe('D-37');
});

it('acepta los códigos de retorno sin importar mayúsculas', function () {
    expect($this->catalogos->get('Codigos-Retorno')->numero)->toBe('codigos-retorno');
});

it('clasifica los códigos de retorno según su rango', function (string $codigo, string $tipo) {
    expect($this->catalogos->buscar('codigos-retorno', $codigo)->get('tipo'))->toBe($tipo);
})->with([
    'excepción de la SUNAT' => ['0100', 'excepcion_sunat'],
    'excepción del contribuyente' => ['1034', 'excepcion_contribuyente'],
    'rechazo' => ['2047', 'rechazo'],
    'último rechazo' => ['3244', 'rechazo'],
    'observación' => ['4000', 'observacion'],
]);

it('lanza excepción si el catálogo no existe', function () {
    $this->catalogos->get('99');
})->throws(CatalogoNoExiste::class, 'No existe el catálogo [99]');

it('responde si un código existe', function () {
    expect($this->catalogos->existe('06', '6'))->toBeTrue()
        ->and($this->catalogos->existe('06', 6))->toBeTrue()
        ->and($this->catalogos->existe('02', 'PEN'))->toBeTrue()
        ->and($this->catalogos->existe('01', '1'))->toBeFalse()
        ->and($this->catalogos->existe('02', 'XYZ'))->toBeFalse();
});

it('devuelve la descripción de un código', function () {
    expect($this->catalogos->descripcion('01', '01'))->toBe('Factura')
        ->and($this->catalogos->descripcion('06', '6'))->toBe('Registro Unico de Contribuyentes')
        ->and($this->catalogos->descripcion('13', '150114'))->toBe('LA MOLINA')
        ->and($this->catalogos->descripcion('25', '10101502'))->toBe('Perros')
        ->and($this->catalogos->descripcion('01', 'no-existe'))->toBeNull();
});

it('devuelve el ítem con sus propiedades adicionales', function () {
    $pen = $this->catalogos->buscar('02', 'PEN');

    expect($pen)->toBeInstanceOf(Item::class)
        ->and($pen->codigo)->toBe('PEN')
        ->and($pen->descripcion)->toBe('sol peruano')
        ->and($pen->get('simbolo'))->toBe('S/')
        ->and($pen->get('decimales'))->toBe(2)
        ->and($pen->has('simbolo'))->toBeTrue()
        ->and($pen->get('no-existe', 'defecto'))->toBe('defecto');
});

it('conserva el código como texto aunque sea numérico', function () {
    $item = $this->catalogos->buscar('06', 6);

    expect($item->codigo)->toBe('6')
        ->and($this->catalogos->get('06')->codigos())->each->toBeString();
});

it('convierte el ítem a arreglo', function () {
    expect($this->catalogos->buscar('22', '01')->toArray())->toBe([
        'codigo' => '01',
        'descripcion' => 'Percepción Venta Interna',
        'porcentaje' => 2,
    ]);
});

it('recorre los ítems y los expone como colección', function () {
    $catalogo = $this->catalogos->get('53');

    expect($catalogo)->toHaveCount(22)
        ->and(iterator_to_array($catalogo))->each->toBeInstanceOf(Item::class)
        ->and($catalogo->items())->toBeInstanceOf(Collection::class)
        // Item::$codigo y no keys(): PHP convierte en entero una clave como "62".
        ->and($catalogo->items()->filter(fn (Item $i) => $i->get('nivel') === 'global')->map->codigo->values()->all())
        ->toContain('02', '03', '62');
});

it('lista los catálogos disponibles en orden', function () {
    $disponibles = $this->catalogos->disponibles();

    expect($disponibles)->toHaveCount(45)
        ->and($disponibles[0])->toBe('01')
        ->and(array_slice($disponibles, 24, 3))->toBe(['25', '25-jerarquia', '26'])
        ->and(array_slice($disponibles, -3))->toBe(['65', 'D-37', 'codigos-retorno']);
});

it('da la jerarquía del código de producto', function () {
    // Los 6 primeros dígitos de un producto más "00" son su clase.
    expect($this->catalogos->descripcion('25-jerarquia', '10101500'))->toBe('Animales de granja')
        ->and($this->catalogos->descripcion('25-jerarquia', '10000000'))->toBe('Material Vivo Vegetal y Animal, Accesorios y Suministros');
});

it('convierte el catálogo en arreglo y JSON con los códigos en lista', function () {
    $catalogo = $this->catalogos->get('26');
    $json = json_decode(json_encode($catalogo), true);

    expect($catalogo->toArray())->toBe($json)
        ->and($json['numero'])->toBe('26')
        ->and($json['items'])->toBeList()->toHaveCount(3)
        ->and($json['items'][0])->toBe(['codigo' => '0', 'descripcion' => 'Sin información']);

    // Un catálogo con códigos que no son 0, 1, 2… también sale como lista.
    expect(json_decode(json_encode($this->catalogos->get('19')), true)['items'])->toBeList();
});

it('lee cada catálogo una sola vez', function () {
    expect($this->catalogos->get('01'))->toBe($this->catalogos->get(1));
});
