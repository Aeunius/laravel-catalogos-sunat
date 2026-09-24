<?php

namespace Aeunius\CatalogosSunat;

use Aeunius\CatalogosSunat\Exceptions\CatalogoNoExiste;
use JsonException;
use RuntimeException;

/**
 * Lee los catálogos de resources/catalogos. Cada uno se lee la primera vez que
 * se pide y se guarda en memoria el resto del proceso.
 */
final class Repositorio
{
    /** @var array<string, Catalogo> */
    private array $cargados = [];

    public function __construct(
        private readonly string $directorio = __DIR__.'/../resources/catalogos',
    ) {}

    /**
     * Los números de catálogo disponibles, en orden: 01, 02, …, 65, D-37.
     *
     * @return list<string>
     */
    public function numeros(): array
    {
        $numeros = [];

        foreach (glob($this->directorio.'/*.json') ?: [] as $archivo) {
            $numero = basename($archivo, '.json');

            if (! str_contains($numero, 'jerarquia')) {
                $numeros[] = $numero;
            }
        }

        usort($numeros, strnatcmp(...));

        return $numeros;
    }

    public function existe(string|int $numero): bool
    {
        return is_file($this->archivo(self::normalizar($numero)));
    }

    /**
     * @throws CatalogoNoExiste
     */
    public function get(string|int $numero): Catalogo
    {
        $numero = self::normalizar($numero);

        return $this->cargados[$numero] ??= $this->leer($numero);
    }

    /**
     * "1" y 1 son el catálogo "01"; "d-37" es "D-37".
     */
    public static function normalizar(string|int $numero): string
    {
        $numero = strtoupper(trim((string) $numero));

        return ctype_digit($numero) ? str_pad($numero, 2, '0', STR_PAD_LEFT) : $numero;
    }

    private function leer(string $numero): Catalogo
    {
        $archivo = $this->archivo($numero);

        if (! is_file($archivo)) {
            throw CatalogoNoExiste::numero($numero, $this->numeros());
        }

        try {
            $datos = json_decode((string) file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("El catálogo {$numero} no es un JSON válido: {$e->getMessage()}", previous: $e);
        }

        if (! is_array($datos) || ! is_array($datos['items'] ?? null)) {
            throw new RuntimeException("El catálogo {$numero} no tiene la estructura esperada.");
        }

        /** @var array<string, array<string, mixed>> $items */
        $items = $datos['items'];

        return new Catalogo(
            $numero,
            is_string($datos['nombre'] ?? null) ? $datos['nombre'] : '',
            is_string($datos['fuente'] ?? null) ? $datos['fuente'] : '',
            $items,
        );
    }

    private function archivo(string $numero): string
    {
        return $this->directorio.'/'.$numero.'.json';
    }
}
