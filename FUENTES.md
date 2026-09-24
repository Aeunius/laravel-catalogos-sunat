# Fuentes de los datos

Los catálogos de `resources/catalogos/` **no se editan a mano**: los genera
`scripts/generar.php` a partir de los archivos de `fuentes/`, que van versionados
porque la SUNAT reemplaza sus publicaciones sin conservar las anteriores.

```bash
make catalogos
```

## Anexo N.° 8 de la SUNAT

La fuente principal es la hoja **Catálogos** de los Excel de reglas de validación
que la SUNAT publica en el [portal CPE](https://cpe.sunat.gob.pe). Es el Anexo
N.° 8 vigente, ya tabulado, y está más al día que los PDF de las resoluciones.

| Archivo | Publicado | Aporta |
|---|---|---|
| `AjustesValidacionesCPEv20250421.xlsx` | 21-04-2025 | Todos los catálogos de comprobantes: 01, 05–12, 14–24, 26, 27, 51–65 |
| `ValidacionesGREv20250421_0.xlsx` | 21-04-2025 | Lo propio de la guía de remisión: el código 19 del catálogo 20, los 7024–7028 del 55, el 91 del 61 y el listado D-37 |

Cuando un catálogo aparece en los dos Excel se suman sus códigos. Si un mismo
código tuviera descripciones distintas, el generador se detiene.

Descargas:
[CPE](https://cpe.sunat.gob.pe/sites/default/files/inline-files/AjustesValidacionesCPEv20250421.xlsx) ·
[GRE](https://cpe.sunat.gob.pe/sites/default/files/inline-files/ValidacionesGREv20250421_0.xlsx)

### Cambios sobre el Excel

- Espacios duros, dobles y saltos de línea se reducen a un espacio.
- Se corrigen seis erratas evidentes. Están listadas en `CORRECCIONES` de
  `scripts/generar.php`; si la SUNAT las corrige, el generador avisa.

  | Catálogo | Código | Dice | Queda |
  |---|---|---|---|
  | 06 | 6 | Contributentes | Contribuyentes |
  | 15 | 2001 | SELVAPARA | SELVA PARA |
  | 17 | 01 | Venta lnterna | Venta Interna |
  | 53 | 63 | Rentención | Retención |
  | 55 | 3050 | Transportre Terreste | Transporte Terrestre |
  | 55 | 5030 | Ciliindrada | Cilindrada |

- Las columnas adicionales se guardan con nombres propios y tipados:

  | Catálogo | Columna del Excel | Propiedad |
  |---|---|---|
  | 05 | Código internacional, Nombre | `codigo_internacional`, `nombre` |
  | 07 | Código de tributo ("1016 o 9996") | `tributos` (lista) |
  | 22 | Porcentaje % | `porcentaje` (número) |
  | 23 | — (la tasa está en la descripción) | `porcentaje` (número) |
  | 24 | Servicio aplicable | `servicio` |
  | 51 | Tipo de comprobante asociado | `comprobantes` (`factura`, `boleta`, `liquidacion_compra`) |
  | 53 | Nivel | `nivel` (`item`, `global`) |
  | 61 | GRE aplicable | `gre` (`remitente`, `transportista`) |
  | 62 | Código de producto SUNAT | `codigo_producto` |
  | 63, 64 | Ubigeo | `ubigeo` |
  | D-37 | Abreviatura | `abreviatura` |

## Catálogos que remiten a estándares externos

El anexo no lista estos catálogos: indica el estándar que hay que usar.

| Catálogo | Estándar | Archivo | Versión |
|---|---|---|---|
| 02 Monedas | ISO 4217 | `iso4217-list-one.xml` | Lista publicada el 17-09-2026 ([SIX](https://www.six-group.com/en/products-services/financial-information/data-standards.html)) |
| 02 Nombres y símbolos | Unicode CLDR, locale `es-PE` | `cldr-es-PE-monedas.json` | `cldr-numbers-full` 48.2.0 |
| 03 Unidades de medida | UN/ECE Recomendación 20 | `rec20-unidades.csv` | Conserva los códigos obsoletos y eliminados, marcados en `estado`: rechazar uno que la SUNAT todavía acepte impediría facturar |
| 04 Países | ISO 3166-1 | `iso3166-paises.csv` | Nombres en español |
| 13 Ubigeo | INEI | `inei-ubigeo-distritos.xlsx` | Límites 2015, 1.874 distritos: el archivo que cita la SUNAT ([datosabiertos](https://www.datosabiertos.gob.pe/dataset/c%C3%B3digo-de-ubicaci%C3%B3n-geogr%C3%A1fica-en-el-per%C3%BA-instituto-nacional-de-estad%C3%ADstica-e-inform%C3%A1tica)) |
| 25 Producto SUNAT | UNSPSC v14_0801 | `unspsc-v14-sunat.xlsm` | Clasificador publicado por la SUNAT |

El ubigeo va en mayúsculas y sin tildes, como lo publica el INEI; solo se
cambia el guion bajo por guion (`ANCO_HUALLO` → `ANCO-HUALLO`) y se quitan las
llamadas a notas al pie de 17 capitales (`MARMOT /13`), cuyas notas el archivo
no trae. Cada distrito lleva además su provincia, departamento y capital legal.

El catálogo 25 se guarda en dos archivos: `25.json` con los 49.022 productos, que
es el nivel que exige el comprobante, y `25-jerarquia.json` con los segmentos,
familias y clases (los 6 primeros dígitos de un producto más `00` son su clase).

## Versionado

El versionado semántico del paquete sigue a los datos:

- corrección de una descripción → *patch*;
- códigos nuevos → *minor*;
- códigos retirados o renumerados → *major*.

El CHANGELOG indica la fecha de los Excel de la SUNAT de cada versión.
