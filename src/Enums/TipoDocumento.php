<?php

namespace Aeunius\CatalogosSunat\Enums;

/**
 * Catálogo 01: código de tipo de documento.
 */
enum TipoDocumento: string
{
    use ConsultaCatalogo;

    case Factura = '01';
    case BoletaVenta = '03';
    case LiquidacionCompra = '04';
    case BoletoTransporteAereo = '05';
    case CartaPorteAereo = '06';
    case NotaCredito = '07';
    case NotaDebito = '08';
    case GuiaRemisionRemitente = '09';
    case PolizaBolsaValores = '11';
    case TicketMaquinaRegistradora = '12';
    case DocumentoBancosSeguros = '13';
    case ReciboServiciosPublicos = '14';
    case BoletoTransporteUrbano = '15';
    case BoletoTransporteInterprovincial = '16';
    case DocumentoAfp = '18';
    case BoletoEspectaculos = '19';
    case ComprobanteRetencion = '20';
    case ConocimientoEmbarque = '21';
    case PolizaAdjudicacion = '23';
    case CertificadoRegaliasPerupetro = '24';
    case EtiquetaTuua = '28';
    case DocumentoCofopri = '29';
    case DocumentoAdquirenteTarjetas = '30';
    case GuiaRemisionTransportista = '31';
    case DocumentoGarantiaRedPrincipal = '32';
    case DocumentoOperador = '34';
    case DocumentoParticipe = '35';
    case ReciboGasNatural = '36';
    case DocumentoRevisionesTecnicas = '37';
    case ComprobantePercepcion = '40';
    case ComprobantePercepcionFisico = '41';
    case DocumentoAdquirenteTarjetasPropias = '42';
    case BoletoAviacionNoRegular = '43';
    case DocumentoCentrosEducativos = '45';
    case BoletoFerroviarioElectronico = '55';
    case ComprobanteSeae = '56';
    case GuiaRemisionRemitenteComplementaria = '71';
    case GuiaRemisionTransportistaComplementaria = '72';
    case NotaCreditoEspecial = '87';
    case NotaDebitoEspecial = '88';

    public static function catalogo(): string
    {
        return '01';
    }

    public function esNota(): bool
    {
        return in_array($this, [self::NotaCredito, self::NotaDebito, self::NotaCreditoEspecial, self::NotaDebitoEspecial], true);
    }

    public function esGuiaRemision(): bool
    {
        return in_array($this, [
            self::GuiaRemisionRemitente,
            self::GuiaRemisionTransportista,
            self::GuiaRemisionRemitenteComplementaria,
            self::GuiaRemisionTransportistaComplementaria,
        ], true);
    }
}
