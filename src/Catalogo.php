<?php

namespace Aeunius\CatalogosSunat;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Un catálogo con sus códigos. Los Item se crean al pedirlos: el catálogo 25
 * tiene 49.022 códigos y validar uno no debería construir los demás.
 *
 * @implements IteratorAggregate<string, Item>
 */
final class Catalogo implements Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  array<string, array<string, mixed>>  $items  código => [descripcion, ...propiedades]
     */
    public function __construct(
        public readonly string $numero,
        public readonly string $nombre,
        public readonly string $fuente,
        private readonly array $items,
    ) {}

    public function existe(string|int $codigo): bool
    {
        return isset($this->items[(string) $codigo]);
    }

    public function buscar(string|int $codigo): ?Item
    {
        $codigo = (string) $codigo;

        if (! isset($this->items[$codigo])) {
            return null;
        }

        return $this->item($codigo, $this->items[$codigo]);
    }

    public function descripcion(string|int $codigo): ?string
    {
        return $this->buscar($codigo)?->descripcion;
    }

    /** @return list<string> */
    public function codigos(): array
    {
        return array_map('strval', array_keys($this->items));
    }

    /** @return Collection<string, Item> */
    public function items(): Collection
    {
        return new Collection(iterator_to_array($this->getIterator()));
    }

    /**
     * Los códigos van en una lista y no en un objeto indexado por código: PHP
     * convierte en enteros claves como "0", "1", "2", y json_encode sacaría un
     * arreglo en unos catálogos y un objeto en otros.
     *
     * @return array{numero: string, nombre: string, fuente: string, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        $items = [];

        foreach ($this as $item) {
            $items[] = $item->toArray();
        }

        return [
            'numero' => $this->numero,
            'nombre' => $this->nombre,
            'fuente' => $this->fuente,
            'items' => $items,
        ];
    }

    /** @return array{numero: string, nombre: string, fuente: string, items: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return Traversable<string, Item> */
    public function getIterator(): Traversable
    {
        $items = [];

        foreach ($this->items as $codigo => $item) {
            $items[(string) $codigo] = $this->item((string) $codigo, $item);
        }

        return new ArrayIterator($items);
    }

    /** @param  array<string, mixed>  $item */
    private function item(string $codigo, array $item): Item
    {
        $descripcion = $item['descripcion'];
        unset($item['descripcion']);

        return new Item($codigo, is_string($descripcion) ? $descripcion : '', $item);
    }
}
