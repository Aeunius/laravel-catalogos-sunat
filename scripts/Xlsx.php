<?php

/**
 * Lector mínimo de .xlsx/.xlsm: solo valores de celda, hoja por hoja y en
 * streaming, para no cargar en memoria los 17 MB de la hoja del UNSPSC.
 * Sin dependencias: ZipArchive y XMLReader vienen con PHP.
 */
final class Xlsx
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private ZipArchive $zip;

    /** @var list<string> */
    private array $sharedStrings = [];

    /** @var array<string, string> nombre de hoja => ruta dentro del zip */
    private array $hojas = [];

    public function __construct(private string $ruta)
    {
        $this->zip = new ZipArchive;

        if ($this->zip->open($ruta) !== true) {
            throw new RuntimeException("No se pudo abrir {$ruta}");
        }

        $this->leerSharedStrings();
        $this->leerHojas();
    }

    /** @return list<string> */
    public function hojas(): array
    {
        return array_keys($this->hojas);
    }

    /**
     * Filas de una hoja: número de fila => [columna => valor], sin celdas vacías.
     *
     * @return Generator<int, array<string, string>>
     */
    public function filas(string $hoja): Generator
    {
        $ruta = $this->hojas[$hoja] ?? throw new RuntimeException("{$this->ruta} no tiene la hoja [{$hoja}]");

        $xml = new XMLReader;
        $xml->XML((string) $this->zip->getFromName($ruta), 'UTF-8', LIBXML_PARSEHUGE);

        $fila = null;
        $celdas = [];
        $columna = '';
        $tipo = '';
        $valor = '';

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT) {
                switch ($xml->localName) {
                    case 'row':
                        $fila = (int) $xml->getAttribute('r');
                        $celdas = [];
                        if ($xml->isEmptyElement) {
                            yield $fila => [];
                        }
                        break;
                    case 'c':
                        if ($xml->isEmptyElement) {
                            // Celda con formato y sin valor: no emite cierre.
                            break;
                        }
                        $columna = preg_replace('/\d+/', '', (string) $xml->getAttribute('r'));
                        $tipo = (string) $xml->getAttribute('t');
                        $valor = '';
                        break;
                    case 'v':
                    case 't':
                        $valor .= $xml->readString();
                        break;
                }
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT) {
                if ($xml->localName === 'c') {
                    if ($tipo === 's') {
                        $valor = $this->sharedStrings[(int) $valor];
                    }
                    if (trim($valor) !== '') {
                        $celdas[$columna] = $valor;
                    }
                } elseif ($xml->localName === 'row') {
                    yield $fila => $celdas;
                }
            }
        }
    }

    private function leerSharedStrings(): void
    {
        $contenido = $this->zip->getFromName('xl/sharedStrings.xml');

        if ($contenido === false) {
            return;
        }

        $xml = new XMLReader;
        $xml->XML($contenido, 'UTF-8', LIBXML_PARSEHUGE);

        $actual = null;

        while ($xml->read()) {
            if ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'si') {
                $actual = '';
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 't' && $actual !== null) {
                $actual .= $xml->readString();
            } elseif ($xml->nodeType === XMLReader::ELEMENT && $xml->localName === 'rPh') {
                // Guías fonéticas: no son parte del texto.
                $xml->next();
            } elseif ($xml->nodeType === XMLReader::END_ELEMENT && $xml->localName === 'si') {
                $this->sharedStrings[] = $actual;
                $actual = null;
            }
        }
    }

    private function leerHojas(): void
    {
        // DOM y no SimpleXML: algunos libros usan un prefijo para el espacio de
        // nombres principal (<x:sheet>) y otros no.
        $relaciones = new DOMDocument;
        $relaciones->loadXML((string) $this->zip->getFromName('xl/_rels/workbook.xml.rels'));

        $destinos = [];
        foreach ($relaciones->getElementsByTagName('Relationship') as $relacion) {
            $destinos[$relacion->getAttribute('Id')] = ltrim($relacion->getAttribute('Target'), '/');
        }

        $libro = new DOMDocument;
        $libro->loadXML((string) $this->zip->getFromName('xl/workbook.xml'));

        foreach ($libro->getElementsByTagNameNS(self::NS, 'sheet') as $hoja) {
            $destino = $destinos[$hoja->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id')];
            $this->hojas[trim($hoja->getAttribute('name'))] = str_starts_with($destino, 'xl/') ? $destino : 'xl/'.$destino;
        }
    }
}
