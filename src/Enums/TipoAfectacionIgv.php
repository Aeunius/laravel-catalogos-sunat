<?php

namespace Aeunius\CatalogosSunat\Enums;

/**
 * Catálogo 07: código de tipo de afectación del IGV.
 */
enum TipoAfectacionIgv: string
{
    use ConsultaCatalogo;

    case GravadoOneroso = '10';
    case GravadoRetiroPremio = '11';
    case GravadoRetiroDonacion = '12';
    case GravadoRetiro = '13';
    case GravadoRetiroPublicidad = '14';
    case GravadoBonificaciones = '15';
    case GravadoRetiroEntregaTrabajadores = '16';
    case GravadoIvap = '17';
    case ExoneradoOneroso = '20';
    case ExoneradoTransferenciaGratuita = '21';
    case InafectoOneroso = '30';
    case InafectoRetiroBonificacion = '31';
    case InafectoRetiro = '32';
    case InafectoRetiroMuestrasMedicas = '33';
    case InafectoRetiroConvenioColectivo = '34';
    case InafectoRetiroPremio = '35';
    case InafectoRetiroPublicidad = '36';
    case InafectoTransferenciaGratuita = '37';
    case Exportacion = '40';

    public static function catalogo(): string
    {
        return '07';
    }

    // El primer dígito del código es el grupo: 1 gravado, 2 exonerado,
    // 3 inafecto, 4 exportación.

    public function esGravado(): bool
    {
        return $this->value[0] === '1';
    }

    public function esExonerado(): bool
    {
        return $this->value[0] === '2';
    }

    public function esInafecto(): bool
    {
        return $this->value[0] === '3';
    }

    public function esExportacion(): bool
    {
        return $this->value[0] === '4';
    }

    /**
     * Los códigos del catálogo 05 con que puede declararse el tributo de la
     * línea. El 17 y el 40 admiten dos: el suyo y el de operación gratuita (9996).
     *
     * @return list<string>
     */
    public function tributos(): array
    {
        /** @var list<string> */
        return $this->item()->get('tributos', []);
    }
}
