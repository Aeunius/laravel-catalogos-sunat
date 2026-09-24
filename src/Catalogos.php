<?php

namespace Aeunius\CatalogosSunat;

use Aeunius\CatalogosSunat\Exceptions\CatalogoNoExiste;

/**
 * Punto de acceso a los catálogos. No depende de Laravel: el facade y el
 * service provider solo lo envuelven.
 *
 *     $catalogos = new Catalogos;
 *     $catalogos->descripcion('06', '6');   // "Registro Único de Contribuyentes"
 *     $catalogos->existe('02', 'PEN');      // true
 */
class Catalogos
{
    private readonly Repositorio $repositorio;

    public function __construct(?Repositorio $repositorio = null)
    {
        $this->repositorio = $repositorio ?? new Repositorio;
    }

    /**
     * @throws CatalogoNoExiste
     */
    public function get(string|int $numero): Catalogo
    {
        return $this->repositorio->get($numero);
    }

    /**
     * Si el código existe en el catálogo. Un catálogo que no existe lanza
     * excepción en vez de devolver false: suele ser un error de programación.
     *
     * @throws CatalogoNoExiste
     */
    public function existe(string|int $numero, string|int $codigo): bool
    {
        return $this->get($numero)->existe($codigo);
    }

    /**
     * @throws CatalogoNoExiste
     */
    public function buscar(string|int $numero, string|int $codigo): ?Item
    {
        return $this->get($numero)->buscar($codigo);
    }

    /**
     * @throws CatalogoNoExiste
     */
    public function descripcion(string|int $numero, string|int $codigo): ?string
    {
        return $this->get($numero)->descripcion($codigo);
    }

    /**
     * Los números de catálogo disponibles: 01, 02, …, 65, D-37.
     *
     * @return list<string>
     */
    public function disponibles(): array
    {
        return $this->repositorio->numeros();
    }
}
