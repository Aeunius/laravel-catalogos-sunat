<?php

namespace Aeunius\CatalogosSunat\Enums;

use Aeunius\CatalogosSunat\Item;
use Aeunius\CatalogosSunat\Repositorio;
use LogicException;

/**
 * Lo común a los enums de catálogo: el caso lleva el código y el catálogo pone
 * la descripción y las propiedades, para no repetir los textos en dos lugares.
 * Un test verifica que los casos coincidan con los códigos del catálogo.
 */
trait ConsultaCatalogo
{
    /** El número del catálogo que representa el enum. */
    abstract public static function catalogo(): string;

    /** El código con todas sus propiedades. */
    public function item(): Item
    {
        return Repositorio::compartido()->get(static::catalogo())->buscar($this->value)
            ?? throw new LogicException(static::class."::{$this->name} no existe en el catálogo ".static::catalogo().'.');
    }

    public function descripcion(): string
    {
        return $this->item()->descripcion;
    }
}
