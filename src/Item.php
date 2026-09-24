<?php

namespace Aeunius\CatalogosSunat;

use JsonSerializable;

/**
 * Un código de un catálogo: el código, su descripción y las propiedades
 * adicionales que tenga ese catálogo (el símbolo de una moneda, el porcentaje
 * de un régimen, los comprobantes de un tipo de operación…). Ver FUENTES.md.
 */
final readonly class Item implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $propiedades
     */
    public function __construct(
        public string $codigo,
        public string $descripcion,
        public array $propiedades = [],
    ) {}

    /** Una propiedad adicional, o $defecto si este código no la tiene. */
    public function get(string $propiedad, mixed $defecto = null): mixed
    {
        return $this->propiedades[$propiedad] ?? $defecto;
    }

    public function has(string $propiedad): bool
    {
        return array_key_exists($propiedad, $this->propiedades);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['codigo' => $this->codigo, 'descripcion' => $this->descripcion, ...$this->propiedades];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
