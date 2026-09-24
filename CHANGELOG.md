# Changelog

Todos los cambios importantes de este paquete se registran aquí.

El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el
proyecto usa [versionado semántico](https://semver.org/lang/es/). Cada versión
indica a qué versión del Anexo N.° 8 de la SUNAT corresponden sus datos.

## [Sin publicar]

## [0.1.0] - 2026-09-23

Primera versión. Datos: Excel de reglas de validación CPE y GRE de la SUNAT del 21-04-2025.

### Agregado

- Los 42 catálogos del Anexo N.° 8 vigente (01–27 y 51–65) y el listado D-37
  de la guía de remisión, con sus propiedades adicionales tipadas.
- Los catálogos que el anexo delega en estándares externos: monedas (ISO 4217,
  con nombre y símbolo en español), unidades de medida (UN/ECE Rec. 20), países
  (ISO 3166-1), ubigeo (INEI) y código de producto (UNSPSC v14, con su
  jerarquía de segmentos, familias y clases).
- `Catalogos::get()`, `existe()`, `buscar()`, `descripcion()` y
  `disponibles()`, también como facade.
- `Catalogo`, que se puede recorrer, contar y convertir en colección, e `Item`,
  con el código, la descripción y las propiedades del catálogo.
- Enums `TipoDocumento` (01), `Moneda` (02), `TipoDocumentoIdentidad` (06) y
  `TipoAfectacionIgv` (07).
- `make catalogos`, que regenera los datos desde las fuentes; ver FUENTES.md.

[Sin publicar]: https://github.com/Aeunius/laravel-catalogos-sunat/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Aeunius/laravel-catalogos-sunat/releases/tag/v0.1.0
