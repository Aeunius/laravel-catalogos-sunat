<?php

/**
 * Genera resources/catalogos/*.json desde los archivos de fuentes/.
 *
 *   make catalogos
 *
 * La fuente principal es la hoja "Catálogos" de los Excel de reglas de
 * validación que publica la SUNAT (CPE y GRE): es el Anexo N.° 8 vigente, ya
 * tabulado. Los catálogos que el anexo delega en estándares externos (02, 03,
 * 04, 13 y 25) se arman desde esos estándares. Ver FUENTES.md.
 *
 * Falla ante cualquier cosa inesperada (una columna nueva, un código repetido,
 * una corrección que ya no aplica) en vez de generar datos a medias.
 */

require __DIR__.'/Xlsx.php';

const FUENTES = __DIR__.'/../fuentes';
const DESTINO = __DIR__.'/../resources/catalogos';

const EXCEL_CPE = 'AjustesValidacionesCPEv20250421.xlsx';
const EXCEL_GRE = 'ValidacionesGREv20250421_0.xlsx';
const FUENTE_SUNAT = 'SUNAT, reglas de validación CPE y GRE del 21-04-2025 (hoja Catálogos)';

/** Catálogos que el anexo no lista: remite a un estándar externo. */
const EXTERNOS = ['02', '03', '04', '13', '25'];

/**
 * Columnas adicionales de cada catálogo del Excel: letra => [propiedad, conversión].
 * La columna A es siempre el código y la B la descripción.
 */
const COLUMNAS = [
    '05' => ['C' => ['codigo_internacional', 'texto'], 'D' => ['nombre', 'texto']],
    '07' => ['C' => ['tributos', 'tributos']],
    '22' => ['C' => ['porcentaje', 'numero']],
    '24' => ['C' => ['servicio', 'minusculas']],
    '51' => ['C' => ['comprobantes', 'comprobantes']],
    '53' => ['C' => ['nivel', 'minusculas']],
    '61' => ['C' => ['gre', 'gre']],
    '62' => ['C' => ['codigo_producto', 'texto']],
    '63' => ['C' => ['ubigeo', 'texto']],
    '64' => ['C' => ['ubigeo', 'texto']],
    'D-37' => ['C' => ['abreviatura', 'texto']],
];

/**
 * Erratas evidentes del Excel de la SUNAT: [catálogo, código, texto, corrección].
 * Si la SUNAT las arregla, el generador avisa para quitarlas de aquí.
 */
const CORRECCIONES = [
    ['06', '6', 'Contributentes', 'Contribuyentes'],
    ['15', '2001', 'SELVAPARA', 'SELVA PARA'],
    ['17', '01', 'lnterna', 'Interna'],
    ['53', '63', 'Rentención', 'Retención'],
    ['55', '3050', 'Transportre Terreste', 'Transporte Terrestre'],
    ['55', '5030', 'Ciliindrada', 'Cilindrada'],
];

$catalogos = catalogosSunat(EXCEL_CPE);

foreach (catalogosSunat(EXCEL_GRE) as $numero => $catalogo) {
    $catalogos[$numero] = isset($catalogos[$numero])
        ? unir($catalogos[$numero], $catalogo)
        : $catalogo;
}

aplicarCorrecciones($catalogos);
porcentajesDeRetencion($catalogos['23']);

$catalogos['02'] = monedas();
$catalogos['03'] = unidadesDeMedida();
$catalogos['04'] = paises();
$catalogos['13'] = ubigeos();
[$catalogos['25'], $jerarquia] = productosUnspsc();

if (! is_dir(DESTINO)) {
    mkdir(DESTINO, 0755, true);
}

foreach (glob(DESTINO.'/*.json') as $archivo) {
    unlink($archivo);
}

uksort($catalogos, 'strnatcmp');

foreach ($catalogos as $numero => $catalogo) {
    ksort($catalogo['items'], SORT_STRING);
    escribir(DESTINO."/{$numero}.json", $catalogo);
    printf("%-5s %6d  %s\n", $numero, count($catalogo['items']), $catalogo['nombre']);
}

escribir(DESTINO.'/25-jerarquia.json', $jerarquia);
printf("%-5s %6d  %s\n", '25', count($jerarquia['items']), 'Jerarquía UNSPSC (segmento, familia, clase)');

// ---------------------------------------------------------------------------
// Excel de la SUNAT
// ---------------------------------------------------------------------------

/**
 * Recorre la hoja "Catálogos". Cada catálogo empieza con una fila "No." y sigue
 * con el nombre, la fila de encabezados y los códigos.
 *
 * @return array<string, array{catalogo: string, nombre: string, fuente: string, items: array<string, array<string, mixed>>}>
 */
function catalogosSunat(string $archivo): array
{
    $catalogos = [];
    $numero = null;
    $estado = null;

    foreach ((new Xlsx(FUENTES.'/'.$archivo))->filas('Catálogos') as $fila => $celdas) {
        $a = limpiar($celdas['A'] ?? '');
        $b = limpiar($celdas['B'] ?? '');

        if ($a === 'No.') {
            $numero = preg_match('/^\d+$/', $b) ? str_pad($b, 2, '0', STR_PAD_LEFT) : $b;
            $estado = 'nombre';
            $catalogos[$numero] = ['catalogo' => $numero, 'nombre' => '', 'fuente' => FUENTE_SUNAT, 'items' => []];

            continue;
        }

        if ($numero === null) {
            continue;
        }

        if ($estado === 'nombre' && preg_match('/^(Catálogo|Descripción)$/iu', $a)) {
            $catalogos[$numero]['nombre'] = $b;
            $estado = 'encabezados';

            continue;
        }

        if ($estado === 'encabezados' && $a !== '') {
            $esperadas = array_merge(['A', 'B'], array_keys(COLUMNAS[$numero] ?? []));
            $sobrantes = array_diff(array_keys($celdas), $esperadas);

            if ($sobrantes !== []) {
                falla("{$archivo} fila {$fila}: el catálogo {$numero} trae columnas que el generador no conoce: ".implode(', ', $sobrantes));
            }

            $estado = 'codigos';

            continue;
        }

        // Los catálogos externos solo traen la referencia al estándar; algunas
        // filas sueltas (un encabezado repetido, un rótulo) no tienen código o
        // no tienen descripción.
        if ($estado !== 'codigos' || in_array($numero, EXTERNOS, true) || $a === '' || $b === '') {
            continue;
        }

        if (isset($catalogos[$numero]['items'][$a])) {
            falla("{$archivo} fila {$fila}: el código {$a} se repite en el catálogo {$numero}");
        }

        $item = ['descripcion' => $b];

        foreach (COLUMNAS[$numero] ?? [] as $columna => [$propiedad, $conversion]) {
            if (isset($celdas[$columna])) {
                $item[$propiedad] = convertir(limpiar($celdas[$columna]), $conversion, "{$archivo} fila {$fila}");
            }
        }

        $catalogos[$numero]['items'][$a] = $item;
    }

    return $catalogos;
}

/**
 * Une un catálogo que aparece en los dos Excel: suma los códigos que faltan y
 * falla si un mismo código dice cosas distintas.
 */
function unir(array $cpe, array $gre): array
{
    foreach ($gre['items'] as $codigo => $item) {
        if (isset($cpe['items'][$codigo]) && $cpe['items'][$codigo] !== $item) {
            falla("El código {$codigo} del catálogo {$cpe['catalogo']} difiere entre los Excel CPE y GRE");
        }

        $cpe['items'][$codigo] = $item;
    }

    return $cpe;
}

function convertir(string $valor, string $conversion, string $donde): mixed
{
    return match ($conversion) {
        'texto' => $valor,
        'minusculas' => mb_strtolower($valor),
        'numero' => is_numeric($valor) ? $valor + 0 : falla("{$donde}: [{$valor}] no es un número"),
        // "1016 o 9996"
        'tributos' => preg_split('/\s+o\s+/', $valor),
        // "Factura, Boletas" / "Liquidación de compra"
        'comprobantes' => array_map(fn (string $c) => match (mb_strtolower($c)) {
            'factura' => 'factura',
            'boleta', 'boletas' => 'boleta',
            'liquidación de compra' => 'liquidacion_compra',
            default => falla("{$donde}: comprobante desconocido [{$c}]"),
        }, preg_split('/\s*,\s*/', $valor)),
        // "remitente, transportista" / "solo remitente"
        'gre' => preg_split('/\s*,\s*/', preg_replace('/^solo\s+/i', '', mb_strtolower($valor))),
    };
}

function aplicarCorrecciones(array &$catalogos): void
{
    foreach (CORRECCIONES as [$numero, $codigo, $texto, $correccion]) {
        $descripcion = $catalogos[$numero]['items'][$codigo]['descripcion'] ?? '';

        if (! str_contains($descripcion, $texto)) {
            falla("La corrección [{$texto}] del código {$codigo} del catálogo {$numero} ya no aplica: quítala de CORRECCIONES");
        }

        $catalogos[$numero]['items'][$codigo]['descripcion'] = str_replace($texto, $correccion, $descripcion);
    }
}

/** El 23 no trae la tasa en una columna, sino en la descripción: "Tasa 3%". */
function porcentajesDeRetencion(array &$catalogo): void
{
    foreach ($catalogo['items'] as $codigo => $item) {
        if (! preg_match('/(\d+(?:\.\d+)?)\s*%/', $item['descripcion'], $m)) {
            falla("El régimen de retención {$codigo} no indica su tasa: [{$item['descripcion']}]");
        }

        $catalogo['items'][$codigo]['porcentaje'] = $m[1] + 0;
    }
}

// ---------------------------------------------------------------------------
// Estándares externos
// ---------------------------------------------------------------------------

/** 02: ISO 4217 (códigos) con el nombre y el símbolo en español de CLDR. */
function monedas(): array
{
    $iso = simplexml_load_file(FUENTES.'/iso4217-list-one.xml');
    $cldr = json_decode(file_get_contents(FUENTES.'/cldr-es-PE-monedas.json'), true)['main']['es-PE']['numbers']['currencies'];

    $items = [];

    foreach ($iso->CcyTbl->CcyNtry as $entrada) {
        $codigo = (string) $entrada->Ccy;

        if ($codigo === '' || isset($items[$codigo])) {
            continue;
        }

        $simbolo = $cldr[$codigo]['symbol-alt-narrow'] ?? $cldr[$codigo]['symbol'] ?? $codigo;
        $decimales = (string) $entrada->CcyMnrUnts;

        $items[$codigo] = array_filter([
            'descripcion' => $cldr[$codigo]['displayName'] ?? (string) $entrada->CcyNm,
            'simbolo' => $simbolo !== $codigo ? $simbolo : null,
            'numerico' => (string) $entrada->CcyNbr,
            'decimales' => is_numeric($decimales) ? (int) $decimales : null,
        ], fn ($valor) => $valor !== null);
    }

    return [
        'catalogo' => '02',
        'nombre' => 'Código de tipo de monedas',
        'fuente' => "ISO 4217, lista publicada el {$iso['Pblshd']}; nombres y símbolos en español de Unicode CLDR (es-PE)",
        'items' => $items,
    ];
}

/** 03: UN/ECE Recomendación 20, incluidos los códigos retirados. */
function unidadesDeMedida(): array
{
    $estados = [
        'Vigente' => 'vigente',
        'Vigente (característica modificada)' => 'vigente',
        'Obsoleto (deprecated)' => 'obsoleto',
        'Eliminado de la Rec. 20' => 'eliminado',
    ];

    $items = [];

    foreach (csv('rec20-unidades.csv') as $fila) {
        $items[$fila['codigo']] = array_filter([
            'descripcion' => $fila['nombre_es'] ?: $fila['nombre_en'],
            'nombre_en' => $fila['nombre_en'],
            'simbolo' => $fila['simbolo'] ?: null,
            'estado' => $estados[$fila['estado']] ?? falla("Unidad {$fila['codigo']}: estado desconocido [{$fila['estado']}]"),
        ], fn ($valor) => $valor !== null);
    }

    return [
        'catalogo' => '03',
        'nombre' => 'Código de tipo de unidad de medida comercial',
        'fuente' => 'UN/ECE Recomendación 20; conserva los códigos obsoletos y eliminados, marcados en "estado"',
        'items' => $items,
    ];
}

/** 04: ISO 3166-1, con los nombres en español. */
function paises(): array
{
    $items = [];

    foreach (csv('iso3166-paises.csv') as $fila) {
        $items[$fila['codigo_alpha2']] = [
            'descripcion' => $fila['nombre_es'],
            'alpha3' => $fila['codigo_alpha3'],
            'numerico' => $fila['codigo_numerico'],
        ];
    }

    return [
        'catalogo' => '04',
        'nombre' => 'Código de país',
        'fuente' => 'ISO 3166-1',
        'items' => $items,
    ];
}

/** 13: ubigeo del INEI. */
function ubigeos(): array
{
    $items = [];

    foreach (csv('ubigeo-inei.csv') as $fila) {
        $items[$fila['Ubigeo']] = [
            'descripcion' => $fila['Distrito'],
            'provincia' => $fila['Provincia'],
            'departamento' => $fila['Departamento'],
        ];
    }

    return [
        'catalogo' => '13',
        'nombre' => 'Código de ubicación geográfica (UBIGEO)',
        'fuente' => 'Ubigeo del INEI',
        'items' => $items,
    ];
}

/**
 * 25: UNSPSC v14 publicado por la SUNAT. Devuelve los productos (nivel que
 * exige el comprobante) y, aparte, la jerarquía de segmentos, familias y clases.
 *
 * @return array{0: array, 1: array}
 */
function productosUnspsc(): array
{
    $productos = [];
    $jerarquia = [];

    foreach ((new Xlsx(FUENTES.'/unspsc-v14-sunat.xlsm'))->filas('Bienes y Servicios') as $celdas) {
        if (! preg_match('/^\d{8}$/', $celdas['G'] ?? '')) {
            continue;
        }

        $productos[$celdas['G']] = ['descripcion' => limpiar($celdas['H'])];

        foreach (['A' => 'B', 'C' => 'D', 'E' => 'F'] as $codigo => $descripcion) {
            $jerarquia[$celdas[$codigo]] ??= ['descripcion' => limpiar($celdas[$descripcion])];
        }
    }

    return [
        [
            'catalogo' => '25',
            'nombre' => 'Código de producto SUNAT',
            'fuente' => 'UNSPSC v14_0801 publicado por la SUNAT (Clasificador de Bienes y Servicios)',
            'items' => $productos,
        ],
        [
            'catalogo' => '25',
            'nombre' => 'Jerarquía del código de producto SUNAT: segmentos, familias y clases',
            'fuente' => 'UNSPSC v14_0801 publicado por la SUNAT (Clasificador de Bienes y Servicios)',
            'items' => $jerarquia,
        ],
    ];
}

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------

/** Espacios duros, dobles y saltos de línea del Excel, a un solo espacio. */
function limpiar(string $texto): string
{
    return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $texto));
}

/** @return Generator<int, array<string, string>> */
function csv(string $archivo): Generator
{
    $f = fopen(FUENTES.'/'.$archivo, 'r');
    $encabezados = fgetcsv($f, escape: '');
    $encabezados[0] = preg_replace('/^\x{FEFF}/u', '', $encabezados[0]);

    while (($fila = fgetcsv($f, escape: '')) !== false) {
        yield array_map('limpiar', array_combine($encabezados, $fila));
    }

    fclose($f);
}

/**
 * Un código por línea: así el diff de una actualización de la SUNAT muestra
 * exactamente qué códigos cambiaron.
 */
function escribir(string $ruta, array $catalogo): void
{
    $json = fn (mixed $valor) => json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $lineas = [];
    foreach ($catalogo['items'] as $codigo => $item) {
        $lineas[] = '    '.$json((string) $codigo).': '.$json($item);
    }

    $cabecera = array_diff_key($catalogo, ['items' => true]);

    $salida = "{\n";
    foreach ($cabecera as $clave => $valor) {
        $salida .= '  '.$json($clave).': '.$json($valor).",\n";
    }
    $salida .= "  \"items\": {\n".implode(",\n", $lineas)."\n  }\n}\n";

    file_put_contents($ruta, $salida);
}

function falla(string $mensaje): never
{
    fwrite(STDERR, "ERROR: {$mensaje}\n");
    exit(1);
}
