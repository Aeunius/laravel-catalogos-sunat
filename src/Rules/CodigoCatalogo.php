<?php

namespace Aeunius\CatalogosSunat\Rules;

use Aeunius\CatalogosSunat\Catalogo;
use Aeunius\CatalogosSunat\Exceptions\CatalogoNoExiste;
use Aeunius\CatalogosSunat\Repositorio;
use BackedEnum;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Que el valor sea un código del catálogo indicado:
 *
 *     'moneda' => ['required', new CodigoCatalogo('02')],
 *     'moneda' => 'required|codigo_catalogo:02',
 *
 * Con donde() además exige una propiedad del código: el tipo de operación que
 * admite boletas, el cargo o descuento a nivel de ítem…
 *
 *     (new CodigoCatalogo('51'))->donde('comprobantes', 'boleta')
 */
final class CodigoCatalogo implements ValidationRule
{
    private readonly Catalogo $catalogo;

    /** @var array<string, string|int|float|bool> */
    private array $condiciones = [];

    /**
     * @throws CatalogoNoExiste si el catálogo no existe: es un error del código,
     *                          no del dato que se valida.
     */
    public function __construct(string|int $catalogo)
    {
        $this->catalogo = Repositorio::compartido()->get($catalogo);
    }

    /**
     * Exige que la propiedad del código tenga ese valor; si la propiedad es una
     * lista (los comprobantes del 51, los tributos del 07), que lo contenga.
     */
    public function donde(string $propiedad, string|int|float|bool $valor): self
    {
        $this->condiciones[$propiedad] = $valor;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        $item = is_string($value) || is_int($value) ? $this->catalogo->buscar($value) : null;

        if ($item === null) {
            $fail('catalogos-sunat::validation.codigo_catalogo')->translate([
                'catalogo' => $this->catalogo->numero,
                'nombre' => $this->catalogo->nombre,
            ]);

            return;
        }

        // El código existe pero no sirve aquí: otro mensaje, o "debe ser un
        // código del catálogo 51" confundiría a quien mandó uno que sí lo es.
        if (! $this->cumple($item->propiedades)) {
            $fail('catalogos-sunat::validation.codigo_catalogo_donde')->translate([
                'catalogo' => $this->catalogo->numero,
                'condiciones' => $this->describirCondiciones(),
            ]);
        }
    }

    private function describirCondiciones(): string
    {
        $partes = [];

        foreach ($this->condiciones as $propiedad => $valor) {
            $partes[] = $propiedad.': '.(is_bool($valor) ? var_export($valor, true) : $valor);
        }

        return implode(', ', $partes);
    }

    /** @param  array<string, mixed>  $propiedades */
    private function cumple(array $propiedades): bool
    {
        foreach ($this->condiciones as $propiedad => $esperado) {
            $valor = $propiedades[$propiedad] ?? null;
            $valores = is_array($valor) ? $valor : [$valor];

            $cumple = false;
            foreach ($valores as $candidato) {
                $cumple = $cumple || self::iguales($candidato, $esperado);
            }

            if (! $cumple) {
                return false;
            }
        }

        return true;
    }

    /**
     * Estricto, salvo entre números: el porcentaje 2 del catálogo 22 es igual
     * a 2.0, pero el código "01" no es igual a 1.
     */
    private static function iguales(mixed $valor, string|int|float|bool $esperado): bool
    {
        if ((is_int($valor) || is_float($valor)) && (is_int($esperado) || is_float($esperado))) {
            return $valor == $esperado;
        }

        return $valor === $esperado;
    }
}
