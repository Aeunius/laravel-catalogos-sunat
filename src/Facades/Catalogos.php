<?php

namespace Aeunius\CatalogosSunat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Aeunius\CatalogosSunat\Catalogos
 */
class Catalogos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Aeunius\CatalogosSunat\Catalogos::class;
    }
}
