<?php

namespace Aeunius\CatalogosSunat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Aeunius\CatalogosSunat\Catalogo get(string|int $numero)
 * @method static bool existe(string|int $numero, string|int $codigo)
 * @method static \Aeunius\CatalogosSunat\Item|null buscar(string|int $numero, string|int $codigo)
 * @method static string|null descripcion(string|int $numero, string|int $codigo)
 * @method static list<string> disponibles()
 *
 * @see \Aeunius\CatalogosSunat\Catalogos
 */
class Catalogos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aeunius\CatalogosSunat\Catalogos::class;
    }
}
