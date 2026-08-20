<?php

namespace App\Services\Payments;

use Carbon\CarbonImmutable;

class RedsysService
{
    public function endpoint(): string
    {
        return rtrim((string) (config('services.redsys.endpoint') ?: config('redsys.url')), '/');
    }

    public function merchantCode(): string
    {
        return (string) (config('services.redsys.merchant_code') ?: config('redsys.MerchantCode'));
    }

    public function terminal(): string
    {
        return (string) (config('services.redsys.terminal') ?: config('redsys.Terminal'));
    }

    public function currency(): string
    {
        return (string) (config('services.redsys.currency') ?: config('redsys.Currency', '978'));
    }

    public function transactionType(): string
    {
        return (string) (config('services.redsys.transaction_type') ?: config('redsys.TransactionType', '0'));
    }

    public function signatureVersion(): string
    {
        return 'HMAC_SHA256_V1';
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{Ds_SignatureVersion:string, Ds_MerchantParameters:string, Ds_Signature:string}
     */
    public function buildRedirectPayload(array $params, string $order): array
    {
        $merchantParameters = $this->encodeMerchantParameters($params);
        $signature = $this->sign($merchantParameters, $order);

        return [
            'Ds_SignatureVersion' => $this->signatureVersion(),
            'Ds_MerchantParameters' => $merchantParameters,
            'Ds_Signature' => $signature,
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function encodeMerchantParameters(array $params): string
    {
        $json = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return base64_encode($json ?: '{}');
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeMerchantParameters(string $merchantParameters): array
    {
        $json = base64_decode($merchantParameters, true) ?: '';
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function verifySignature(string $merchantParameters, string $signature, string $order): bool
    {
        $expected = $this->sign($merchantParameters, $order);

        return hash_equals($expected, $signature);
    }

    public function isSuccessfulResponse(?string $responseCode): bool
    {
        if ($responseCode === null || $responseCode === '') {
            return false;
        }

        $code = (int) $responseCode;

        return $code >= 0 && $code < 100;
    }

    public function parseGatewayDateTime(?string $date, ?string $hour): ?CarbonImmutable
    {
        $date = trim((string) $date);
        $hour = trim((string) $hour);

        if ($date === '' || $hour === '') {
            return null;
        }

        // Expected formats: Ds_Date = dd/mm/yyyy, Ds_Hour = hh:mm
        try {
            return CarbonImmutable::createFromFormat('d/m/Y H:i', $date.' '.$hour);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sign(string $merchantParameters, string $order): string
    {
        $secret = (string) (config('services.redsys.secret_key_base64') ?: config('redsys.Key'));
        $key = base64_decode($secret, true);

        if ($key === false) {
            return '';
        }

        // Derived key: 3DES (DES-EDE3-CBC) encrypt(order) with IV = 0.
        $iv = str_repeat("\0", 8);
        $derivedKey = openssl_encrypt($order, 'DES-EDE3-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($derivedKey === false) {
            return '';
        }

        // HMAC over decoded merchant parameters bytes (not the base64 string).
        $data = base64_decode($merchantParameters, true);
        if ($data === false) {
            return '';
        }

        $hmac = hash_hmac('sha256', $data, $derivedKey, true);

        return $this->base64UrlEncode($hmac);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
