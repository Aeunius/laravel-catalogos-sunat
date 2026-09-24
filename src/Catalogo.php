<?php

namespace Aeunius\CatalogosSunat;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * Un catálogo con sus códigos. Los Item se crean al pedirlos: el catálogo 25
 * tiene 49.022 códigos y validar uno no debería construir los demás.
 *
 * @implements IteratorAggregate<string, Item>
 */
final class Catalogo implements Countable, IteratorAggregate
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
