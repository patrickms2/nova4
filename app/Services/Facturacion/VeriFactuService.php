<?php

namespace App\Services\Facturacion;

use App\Models\Factura;
use Illuminate\Support\Facades\Log;
use Squareetlabs\VeriFactu\Helpers\QrUrlHelper;
use Squareetlabs\VeriFactu\Services\AeatClient;

class VeriFactuService
{
    public function __construct(
        private AeatClient $aeatClient,
    ) {}

    /**
     * Send a Factura to AEAT VeriFactu and store the response.
     *
     * @param Factura $factura
     * @return array{status: string, hash: string|null, message: string|null, request: string|null, response: string|null}
     */
    public function enviar(Factura $factura): array
    {
        if ($factura->isVeriFactuSent()) {
            return [
                'status' => 'skipped',
                'message' => 'La factura ya ha sido enviada a VeriFactu.',
                'hash' => $factura->verifactu_hash,
                'request' => null,
                'response' => null,
            ];
        }

        if ($factura->verifactu_status === 'rejected') {
            $factura->update([
                'verifactu_status' => null,
                'verifactu_hash' => null,
                'verifactu_previous_hash' => null,
                'verifactu_response_code' => null,
                'verifactu_response_message' => null,
                'verifactu_sent_at' => null,
                'verifactu_request_xml' => null,
                'verifactu_response_xml' => null,
                'verifactu_qr_url' => null,
            ]);
        }

        $empresa = $factura->empresa;
        $issuerName = $empresa?->empresa ?: config('verifactu.issuer.name', '');
        $issuerVat = $empresa?->nif ?: config('verifactu.issuer.vat', '');

        if (empty($issuerVat)) {
            throw new \RuntimeException('No se ha configurado el NIF del emisor para VeriFactu.');
        }

        $previous = $this->buildPrevious($factura);

        $originalIssuer = config('verifactu.issuer');
        config(['verifactu.issuer' => ['name' => $issuerName, 'vat' => $issuerVat]]);

        try {
            $result = $this->aeatClient->sendInvoice($factura, $previous);
        } finally {
            config(['verifactu.issuer' => $originalIssuer]);
        }

        $responseXml = $result['response'] ?? null;
        $aeatResult = $responseXml ? $this->parseAeatResponse($responseXml) : null;

        if ($result['status'] === 'success' && $aeatResult && $aeatResult['isAccepted']) {
            $factura->update([
                'verifactu_status' => 'accepted',
                'verifactu_hash' => $result['hash'],
                'verifactu_previous_hash' => $previous['hash'] ?? null,
                'verifactu_response_code' => $aeatResult['code'] ?? '0',
                'verifactu_response_message' => $aeatResult['message'] ?? 'Registro aceptado por AEAT',
                'verifactu_sent_at' => now(),
                'verifactu_request_xml' => $result['request'] ?? null,
                'verifactu_response_xml' => $responseXml,
            ]);

            $factura->update([
                'verifactu_qr_url' => QrUrlHelper::build(
                    $factura,
                    $issuerVat,
                    (bool) config('verifactu.aeat.production', false)
                ),
            ]);

            Log::info('Factura enviada a VeriFactu', [
                'factura_id' => $factura->id,
                'codfactura' => $factura->codfactura,
                'hash' => $result['hash'],
            ]);
        } elseif ($result['status'] === 'success' && $aeatResult && ! $aeatResult['isAccepted']) {
            $factura->update([
                'verifactu_status' => 'rejected',
                'verifactu_hash' => $result['hash'] ?? null,
                'verifactu_previous_hash' => $previous['hash'] ?? null,
                'verifactu_response_code' => $aeatResult['code'] ?? null,
                'verifactu_response_message' => $aeatResult['message'] ?? 'Registro rechazado por AEAT',
                'verifactu_sent_at' => now(),
                'verifactu_request_xml' => $result['request'] ?? null,
                'verifactu_response_xml' => $responseXml,
            ]);

            Log::error('Error enviando factura a VeriFactu', [
                'factura_id' => $factura->id,
                'codfactura' => $factura->codfactura,
                'message' => $aeatResult['message'] ?? 'Registro rechazado por AEAT',
            ]);
        } else {
            $factura->update([
                'verifactu_status' => 'rejected',
                'verifactu_response_message' => $result['message'] ?? 'Error desconocido',
                'verifactu_sent_at' => now(),
                'verifactu_request_xml' => $result['request'] ?? null,
                'verifactu_response_xml' => $responseXml,
            ]);

            Log::error('Error enviando factura a VeriFactu', [
                'factura_id' => $factura->id,
                'codfactura' => $factura->codfactura,
                'message' => $result['message'] ?? 'Error desconocido',
            ]);
        }

        return [
            'status' => $result['status'],
            'hash' => $result['hash'] ?? null,
            'message' => $result['message'] ?? null,
            'request' => $result['request'] ?? null,
            'response' => $result['response'] ?? null,
        ];
    }

    /**
     * Parse the AEAT VeriFactu response XML to determine acceptance.
     *
     * @return array{isAccepted: bool, code: string|null, message: string|null}|null
     */
    private function parseAeatResponse(string $xml): ?array
    {
        if (trim($xml) === '') {
            return null;
        }

        $previousErrors = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_use_internal_errors($previousErrors);

        if (! $doc instanceof \SimpleXMLElement) {
            return null;
        }

        $namespaces = $doc->getNamespaces(true);
        $respNs = $namespaces['tikR'] ?? 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaSuministro.xsd';
        $infoNs = $namespaces['tik'] ?? 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd';
        $doc->registerXPathNamespace('tikR', $respNs);
        $doc->registerXPathNamespace('tik', $infoNs);

        $estadoEnvio = $this->firstXPathValue($doc, '//tikR:EstadoEnvio');
        $estadoRegistro = $this->firstXPathValue($doc, '//tikR:EstadoRegistro');
        $codigoError = $this->firstXPathValue($doc, '//tikR:CodigoErrorRegistro');
        $descripcionError = $this->firstXPathValue($doc, '//tikR:DescripcionErrorRegistro');
        $estadoDuplicado = $this->firstXPathValue($doc, '//tikR:RegistroDuplicado/tik:EstadoRegistroDuplicado');

        $isAccepted = (in_array($estadoEnvio, ['Correcto', 'Correcta'], true)
            && in_array($estadoRegistro, ['Correcto', 'Correcta'], true))
            || ($codigoError === '3000' && in_array($estadoDuplicado, ['Correcto', 'Correcta', 'AceptadaConErrores'], true));

        return [
            'isAccepted' => $isAccepted,
            'code' => $codigoError ?: null,
            'message' => $descripcionError ?: null,
        ];
    }

    /**
     * @return string|null
     */
    private function firstXPathValue(\SimpleXMLElement $xml, string $xpath): ?string
    {
        $result = $xml->xpath($xpath);

        if (empty($result) || ! ($result[0] instanceof \SimpleXMLElement)) {
            return null;
        }

        $value = trim((string) $result[0]);

        return $value === '' ? null : $value;
    }

    /**
     * Build the previous invoice data for VeriFactu chaining.
     *
     * @param Factura $factura
     * @return array{hash: string, number: string, date: string}|null
     */
    private function buildPrevious(Factura $factura): ?array
    {
        $previous = $factura->previousVeriFactu();

        if (! $previous) {
            return null;
        }

        return [
            'hash' => (string) $previous->verifactu_hash,
            'number' => (string) $previous->codfactura,
            'date' => $previous->fechaemitido->format('d-m-Y'),
        ];
    }
}
