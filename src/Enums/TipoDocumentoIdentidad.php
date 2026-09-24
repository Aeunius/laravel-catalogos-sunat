<?php

namespace Aeunius\CatalogosSunat\Enums;

/**
 * Catálogo 06: código de tipo de documento de identidad. Los nombres de los
 * casos son los mismos del enum TipoDocumento de aeunius/laravel-peru-rules.
 */
enum TipoDocumentoIdentidad: string
{
    use ConsultaCatalogo;

    case NoDomiciliadoSinRuc = '0';
    case Dni = '1';
    case CarneExtranjeria = '4';
    case Ruc = '6';
    case Pasaporte = '7';
    case CedulaDiplomatica = 'A';
    case DocumentoPaisResidencia = 'B';
    case TaxIdentificationNumber = 'C';
    case IdentificationNumber = 'D';
    case TarjetaAndinaMigracion = 'E';
    case PermisoTemporalPermanencia = 'F';
    case Salvoconducto = 'G';
    case CarnePermisoTemporalPermanencia = 'H';

    public static function catalogo(): string
    {
        return '06';
    }
}
