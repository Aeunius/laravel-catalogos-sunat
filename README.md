# Catálogos de la SUNAT para Laravel

Los catálogos del **Anexo N.° 8** de la facturación electrónica de la SUNAT como
datos consultables desde PHP: tipos de documento, monedas, unidades de medida,
tributos, afectación del IGV, tipos de operación, ubigeo, código de producto
(UNSPSC), detracciones, medios de pago y el resto.

[![tests](https://github.com/Aeunius/laravel-catalogos-sunat/actions/workflows/tests.yml/badge.svg)](https://github.com/Aeunius/laravel-catalogos-sunat/actions/workflows/tests.yml)
[![Versión en Packagist](https://img.shields.io/packagist/v/aeunius/laravel-catalogos-sunat.svg)](https://packagist.org/packages/aeunius/laravel-catalogos-sunat)
[![Descargas](https://img.shields.io/packagist/dt/aeunius/laravel-catalogos-sunat.svg)](https://packagist.org/packages/aeunius/laravel-catalogos-sunat)
[![Licencia](https://img.shields.io/packagist/l/aeunius/laravel-catalogos-sunat.svg)](LICENSE.md)

- Los 42 catálogos vigentes (01–27 y 51–65) y el listado D-37 de la guía de
  remisión, tomados de las reglas de validación que publica la SUNAT.
- Los 2.020 códigos de retorno de la SUNAT (excepciones, rechazos y
  observaciones), clasificados según el manual del programador.
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

Un catálogo se convierte en arreglo o JSON con sus códigos en una lista, así
que se puede devolver tal cual desde un controlador:

```php
return Catalogos::get('02');
// {"numero":"02","nombre":"Código de tipo de monedas","fuente":"…",
//  "items":[{"codigo":"AED","descripcion":"dírham de los Emiratos Árabes Unidos",…},…]}
```

El código de producto (25) tiene aparte su jerarquía de segmentos, familias y
clases. Los 6 primeros dígitos de un producto más `00` son su clase:

```php
Catalogos::descripcion('25', '10101502');            // "Perros"
Catalogos::descripcion('25-jerarquia', '10101500');  // "Animales de granja"
```

Los códigos con que responde la SUNAT están en `codigos-retorno`, con su tipo:

```php
$error = Catalogos::buscar('codigos-retorno', $cdr->codigo);
$error->descripcion;                  // "El documento ya fue presentado anteriormente."
$error->get('tipo');                  // "observacion": el comprobante quedó aceptado
```

El número de catálogo se acepta con o sin cero a la izquierda (`'6'`, `6`,
`'06'`). Pedir un catálogo que no existe lanza `CatalogoNoExiste`.

Los códigos siempre son texto en `$item->codigo`. Las claves de `items()` no lo
garantizan, porque PHP convierte en entero una clave como `"62"`.

Cada catálogo se lee la primera vez que se pide y queda en memoria hasta el fin
del proceso. Casi todos son instantáneos; el 25, con 49.022 productos, tarda unos
15 ms y ocupa unos 30 MB.

<details>
<summary>Catálogos incluidos</summary>

| Número | Nombre | Códigos |
|---|---|--:|
| `01` | Código de tipo de documento | 40 |
| `02` | Código de tipo de monedas | 178 |
| `03` | Código de tipo de unidad de medida comercial | 2.136 |
| `04` | Código de país | 249 |
| `05` | Código de tipos de tributos y otros conceptos | 10 |
| `06` | Código de tipo de documento de identidad | 13 |
| `07` | Código de tipo de afectación del IGV | 19 |
| `08` | Código de tipos de sistema de cálculo del ISC | 3 |
| `09` | Códigos de tipo de nota de crédito electrónica | 13 |
| `10` | Códigos de tipo de nota de débito electrónica | 5 |
| `11` | Códigos de tipo de valor de venta (Resumen diario de boletas y notas) | 5 |
| `12` | Código de documentos relacionados tributarios | 11 |
| `13` | Código de ubicación geográfica (UBIGEO) | 1.874 |
| `14` | Código de otros conceptos tributarios | 12 |
| `15` | Códigos de elementos adicionales en la factura y boleta electrónica | 45 |
| `16` | Código de tipo de precio de venta unitario | 3 |
| `17` | Código de tipo de operación | 20 |
| `18` | Código de modalidad de transporte | 2 |
| `19` | Código de estado del ítem (resumen diario) | 3 |
| `20` | Código de motivo de traslado | 14 |
| `21` | Código de documentos relacionados (sólo guía de remisión electrónica) | 6 |
| `22` | Código de regimen de percepciones | 3 |
| `23` | Código de regimen de retenciones | 2 |
| `24` | Código de tarifa de servicios públicos | 49 |
| `25` | Código de producto SUNAT | 49.022 |
| `25-jerarquia` | Jerarquía del código de producto SUNAT: segmentos, familias y clases | 4.294 |
| `26` | Tipo de préstamo (créditos hipotecarios) | 3 |
| `27` | Indicador de primera vivienda | 4 |
| `51` | Código de tipo de operación | 31 |
| `52` | Códigos de leyendas | 15 |
| `53` | Códigos de cargos, descuentos y otras deducciones | 22 |
| `54` | Códigos de bienes y servicios sujetos a detracciones | 39 |
| `55` | Código de identificación del concepto tributario | 120 |
| `56` | Código de tipo de servicio público | 7 |
| `57` | Código de tipo de servicio públicos - telecomunicaciones | 4 |
| `58` | Código de tipo de medidor (recibo de luz) | 2 |
| `59` | Medios de Pago | 22 |
| `60` | Código de tipo de dirección | 5 |
| `61` | Documentos relacionados al transporte de mercancías | 27 |
| `62` | Bienes normalizados | 52 |
| `63` | Puertos del Perú | 21 |
| `64` | Aeropuertos del Perú | 31 |
| `65` | Código de unidades de medida (para uso solo para la GRE en DAM o DS) | 97 |
| `D-37` | Entidades que emiten autorizaciones especiales para el traslado | 12 |
| `codigos-retorno` | Códigos de retorno de la SUNAT: excepciones, rechazos y observaciones | 2.020 |

</details>

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

### Validación

```php
use Aeunius\CatalogosSunat\Rules\CodigoCatalogo;

$request->validate([
    'tipo_doc'  => ['required', new CodigoCatalogo('01')],
    'moneda'    => 'required|codigo_catalogo:02',

    // Además de existir, el código debe cumplir una propiedad del catálogo:
    // un tipo de operación que admita boletas, un descuento a nivel global…
    'operacion' => ['required', (new CodigoCatalogo('51'))->donde('comprobantes', 'boleta')],
    'descuento' => ['required', (new CodigoCatalogo('53'))->donde('nivel', 'global')],
]);
```

Acepta texto, enteros y los enums del paquete. Los mensajes vienen en español e
inglés, y se publican con
`php artisan vendor:publish --tag=catalogos-sunat-translations`.

### Con laravel-peru-rules

[aeunius/laravel-peru-rules](https://github.com/Aeunius/laravel-peru-rules) valida
el número del documento de identidad según su tipo (dígito verificador del RUC,
8 dígitos del DNI…). Los dos paquetes usan los códigos del catálogo 06, así que
se combinan sin configurar nada:

```php
use Aeunius\CatalogosSunat\Rules\CodigoCatalogo;
use Aeunius\PeruRules\Rules\DocumentoIdentidad;

$request->validate([
    'tipo_doc' => ['required', new CodigoCatalogo('06')],
    'num_doc'  => ['required', DocumentoIdentidad::segun('tipo_doc')],
]);
```

Ninguno depende del otro: se instala uno, el otro o los dos.

### Sin Laravel

```php
use Aeunius\CatalogosSunat\Catalogos;

$catalogos = new Catalogos;
$catalogos->descripcion('06', '6');   // "Registro Unico de Contribuyentes"
```

## En JavaScript

[`@aeunius/catalogos-sunat`](https://github.com/Aeunius/catalogos-sunat-js) trae
los mismos catálogos al navegador y a Node, con los mismos datos: el frontend
llena sus selects con los mismos códigos que valida el backend, sin copiar JSON
a mano ni pedirlos a la API.

```bash
npm install @aeunius/catalogos-sunat
```

```ts
import { descripcion, opciones } from '@aeunius/catalogos-sunat';
import monedas from '@aeunius/catalogos-sunat/catalogos/02';

descripcion(monedas, 'PEN'); // "sol peruano"
opciones(monedas);           // [{ value: 'AED', label: 'dírham …' }, …]
```

Cada catálogo es un módulo aparte, así que el 25 no entra en una app que solo
usa monedas. Acepta también el JSON de `Catalogos::get()`, con la misma forma.

Si cambias los datos, publica un tag: el paquete de JavaScript toma sus
catálogos de un tag de este repositorio y sale con el mismo número de versión.

## De dónde salen los datos

De los Excel de reglas de validación que la SUNAT publica en su portal CPE, que
traen el Anexo N.° 8 vigente, y de los estándares a los que el anexo remite (ISO
4217, UN/ECE Rec. 20, ISO 3166-1, ubigeo del INEI, UNSPSC). El detalle, con
versiones y fechas, está en [FUENTES.md](FUENTES.md).

## Versionado

Desde la 1.0 el paquete sigue el [versionado semántico](https://semver.org/lang/es/)
también en los datos:

| Cambio | Versión |
|---|---|
| Se corrige una descripción | *patch* (1.0.1) |
| La SUNAT agrega códigos o catálogos | *minor* (1.1.0) |
| La SUNAT retira o renumera códigos | *major* (2.0.0) |

Quedan cubiertos por la compatibilidad las clases públicas (`Catalogos`,
`Catalogo`, `Item`, los enums, `CodigoCatalogo`), los números de catálogo y los
nombres de las propiedades adicionales (`simbolo`, `comprobantes`, `nivel`,
`tipo`…). El CHANGELOG indica en cada versión la fecha de los datos de la SUNAT.

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
