<?php

namespace Aeunius\CatalogosSunat\Exceptions;

use InvalidArgumentException;

class CatalogoNoExiste extends InvalidArgumentException
{
    /**
     * @param  list<string>  $disponibles
     */
    public static function numero(string $numero, array $disponibles): self
    {
        return new self("No existe el catálogo [{$numero}]. Disponibles: ".implode(', ', $disponibles).'.');
    }
}
