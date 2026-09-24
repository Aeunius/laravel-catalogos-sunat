# Catálogos de la SUNAT para Laravel

Los catálogos del **Anexo N.° 8** de la facturación electrónica de la SUNAT como
datos consultables desde PHP: tipos de documento, monedas, unidades de medida,
tributos, afectación del IGV, tipos de operación, ubigeo, código de producto
(UNSPSC), detracciones, medios de pago y el resto.

- Los 42 catálogos vigentes (01–27 y 51–65) y el listado D-37 de la guía de
  remisión, tomados de las reglas de validación que publica la SUNAT.
- Las propiedades adicionales de cada catálogo, tipadas: el símbolo de la
  moneda, el porcentaje de la percepción, los comprobantes que admite cada tipo
  de operación, el nivel de cada cargo o descuento…
- Sin conexión ni base de datos: los datos van dentro del paquete y cada
  catálogo se lee solo cuando se usa.
- Funciona con Laravel 12 y 13, y también sin Laravel.

## Instalación

```bash
composer require aeunius/laravel-catalogos-sunat
```

Requiere PHP 8.2 o superior. En Laravel el service provider y el facade se
registran solos.

## Uso

```php
use Aeunius\CatalogosSunat\Facades\Catalogos;

Catalogos::descripcion('01', '01');   // "Factura"
Catalogos::existe('06', '6');         // true
Catalogos::existe('51', '0102');      // false: código de 2017, ya no vigente

$pen = Catalogos::buscar('02', 'PEN');
$pen->descripcion;                    // "sol peruano"
$pen->get('simbolo');                 // "S/"
$pen->get('decimales');               // 2

Catalogos::buscar('22', '01')->get('porcentaje');      // 2
Catalogos::buscar('51', '0112')->get('comprobantes');  // ["factura"]
```

Un catálogo completo se recorre o se convierte en colección:

```php
$catalogo = Catalogos::get('53');
$catalogo->nombre;                    // "Códigos de cargos, descuentos y otras deducciones"
count($catalogo);                     // 22

$globales = $catalogo->items()
    ->filter(fn ($item) => $item->get('nivel') === 'global');
```

El número de catálogo se acepta con o sin cero a la izquierda (`'6'`, `6`,
`'06'`). Pedir un catálogo que no existe lanza `CatalogoNoExiste`.

Los códigos siempre son texto en `$item->codigo`. Las claves de `items()` no lo
garantizan, porque PHP convierte en entero una clave como `"62"`.

### Enums

Los catálogos que más se usan en el código también están como enums, con el
código de la SUNAT como valor:

```php
use Aeunius\CatalogosSunat\Enums\Moneda;
use Aeunius\CatalogosSunat\Enums\TipoAfectacionIgv;
use Aeunius\CatalogosSunat\Enums\TipoDocumento;
use Aeunius\CatalogosSunat\Enums\TipoDocumentoIdentidad;

TipoDocumento::Factura->value;                // "01"
TipoDocumento::from('07');                    // TipoDocumento::NotaCredito
TipoDocumento::NotaCredito->esNota();         // true
TipoDocumento::Factura->descripcion();        // "Factura"

TipoDocumentoIdentidad::Ruc->value;           // "6"

TipoAfectacionIgv::GravadoOneroso->esGravado();   // true
TipoAfectacionIgv::GravadoIvap->tributos();       // ["1016", "9996"]

Moneda::PEN->simbolo();                       // "S/"
Moneda::PEN->decimales();                     // 2
```

| Enum | Catálogo |
|---|---|
| `TipoDocumento` | 01 Tipo de documento |
| `Moneda` | 02 Monedas (ISO 4217) |
| `TipoDocumentoIdentidad` | 06 Tipo de documento de identidad |
| `TipoAfectacionIgv` | 07 Tipo de afectación del IGV |

La descripción y las propiedades salen del catálogo, no del enum, y un test
verifica que cada enum tenga exactamente los códigos de su catálogo. Para el
resto de catálogos, `Catalogos::get()`.

### Sin Laravel

```php
use Aeunius\CatalogosSunat\Catalogos;

$catalogos = new Catalogos;
$catalogos->descripcion('06', '6');   // "Registro Unico de Contribuyentes"
```

## De dónde salen los datos

De los Excel de reglas de validación que la SUNAT publica en su portal CPE, que
traen el Anexo N.° 8 vigente, y de los estándares a los que el anexo remite (ISO
4217, UN/ECE Rec. 20, ISO 3166-1, ubigeo del INEI, UNSPSC). El detalle, con
versiones y fechas, está en [FUENTES.md](FUENTES.md).

## Desarrollo

Todo corre en Docker; no hace falta PHP en el equipo.

```bash
make install     # dependencias
make test        # Pest
make analyse     # PHPStan
make lint        # Pint, sin cambiar nada
make catalogos   # regenera resources/catalogos desde fuentes/
```

## Licencia

El código es MIT; ver [LICENSE.md](LICENSE.md). Los catálogos son información
pública de la SUNAT y de los organismos de estandarización citados en
[FUENTES.md](FUENTES.md).
