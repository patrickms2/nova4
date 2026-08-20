<?php

namespace App\Http\Controllers;

use ApproTickets\Enums\PaymentStatus;
use ApproTickets\Models\Option;
use ApproTickets\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Taxi\Pago;
use Redsys\Tpv\Signature;

use Redsys\Tpv\Tpv;

class RedsysController extends Controller
{
    public function pay(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'importe' => ['required', 'numeric', 'min:0.01'],
            'referencia' => ['nullable', 'string', 'max:50'],
        ]);

        $amountInCents = (int)round($data['importe'] * 100);

        // El pedido debe ser único, de 4 a 12 dígitos
        $order = Str::padLeft((string)random_int(1, 99999999), 12, '0');

        $merchantCode = (string)config('services.redsys.merchant_code', env('REDSYS_MERCHANT_CODE', ''));
        $terminal = (string)config('services.redsys.terminal', env('REDSYS_TERMINAL', '1'));
        $secretKey = (string)config('services.redsys.secret_key', env('REDSYS_SECRET_KEY', ''));

        if ($merchantCode === '' || $secretKey === '') {
            abort(500, 'Configuración de Redsys incompleta. Define REDSYS_MERCHANT_CODE y REDSYS_SECRET_KEY en .env');
        }

        $merchantParameters = [
            'DS_MERCHANT_AMOUNT' => $amountInCents,
            'DS_MERCHANT_ORDER' => $order,
            'DS_MERCHANT_MERCHANTCODE' => $merchantCode,
            'DS_MERCHANT_CURRENCY' => '978', // EUR
            'DS_MERCHANT_TRANSACTIONTYPE' => '1',   // Compra
            'DS_MERCHANT_TERMINAL' => $terminal,
            'DS_MERCHANT_TITULAR' => $data['nombre'],
            'DS_MERCHANT_PRODUCTDESCRIPTION' => 'Pago Taxi ' . ($data['referencia'] ?? $order),
            'DS_MERCHANT_CONSUMERLANGUAGE' => '001', // Español
            'DS_MERCHANT_MERCHANTURL' => route('redsys.callback'),
            'DS_MERCHANT_URLOK' => route('redsys.ok'),
            'DS_MERCHANT_URLKO' => route('redsys.ko'),
        ];

        $parametersBase64 = base64_encode(json_encode($merchantParameters, JSON_UNESCAPED_SLASHES));

        $signature = $this->generateSignature(
            merchantParametersBase64: $parametersBase64,
            order: $order,
            secretKeyBase64: $secretKey
        );

        $redsysUrl = (string)config('services.redsys.url', env('REDSYS_URL', 'https://sis-t.redsys.es/sis/realizarPago'));
        $signatureVersion = 'HMAC_SHA256_V1';

        return response()->view('redsys.submit', [
            'action' => $redsysUrl,
            'signatureVersion' => $signatureVersion,
            'merchantParameters' => $parametersBase64,
            'signature' => $signature,
        ]);
    }

    public function payFromPago(Pago $pago)
    {

        $settings = array(
            'Environment' => 'test', // Puedes indicar test o real
            'MerchantCode' => '154205413',
            'Key' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
            'Terminal' => '100',
            'TransactionType' => '1',
            'Currency' => '978',
            'MerchantName' => 'TaxiLanz',
            'Titular' => 'Mi Comercio',
            'ConsumerLanguage' => '001',
            'SignatureVersion' => 'HMAC_SHA256_V1',
            'Merchant_URL' => 'https://admin.taxilanz.com/redsys/callback',
            'UrlOK' => 'https://admin.taxilanz.com/redsys/ok',
            'UrlKO' => 'https://admin.taxilanz.com/redsys/ko',
        );
        $TPV = new Tpv($settings);

        $pagado = str_replace(',', ".", $pago->pagado);
        $pagado2 = $pagado;
        $pagado = $pagado;

        $total = $pagado;
        if ($pago->referencia == null) $pago->referencia = $pago->refID($pago->id);
        $referencia = $pago->referencia;

        $description = "Reserva " . $pago->referencia . " ( " . $pago->recogida . " ) " . $pago->telefono;

        $amount = (float)($pago->pagado ?? 0);
        $amountInCents = (int)round($amount * 100);
        if ($amountInCents < 1) {
            $amount = $pago->importe;
            //abort(422, 'Importe inválido para pago');
        }

        // ORDER: 4-12 dígitos, solo números. Usamos el ID del pago acolchado.
        $order = str_pad((string)$pago->id, 12, '0', STR_PAD_LEFT);

        $TPV->setFormHiddens(array(
            'TransactionType' => '0',
            'Amount' => $amount,
            'Order' => $referencia,
            'MerchantURL' => 'https://nova-mcp.test/redsys/callback',
            'MerchantData' => $description,


        ));

        return '<form action="' . $TPV->getPath('/realizarPago') . '" method="post">' . $TPV->getFormHiddens() . '</form><script>document.forms[0].submit();</script>';

        $order = '012121323';

        //$parametersBase64 = base64_encode(json_encode($merchantParameters, JSON_UNESCAPED_SLASHES));

        /*$signature = $this->generateSignature(
            merchantParametersBase64: $parametersBase64,
            order: $order,
            secretKeyBase64: $secretKey
        );*/
        $signatureVersion = 'HMAC_SHA256_V1';
        $redsysUrl = 'https://sis-t.redsys.es:25443/sis/realizarPago';

        return response()->view('redsys.submit', [
            'action' => $redsysUrl,
            'signatureVersion' => $signatureVersion,
            'merchantParameters' => $TPV->getFormHiddens(),
            'signature' => $TPV->getValuesSignature(),
        ]);
    }

    public function formPago2(Request $request)
    {


        $settings = array(
            'name' => 'TaxiLanz',
            'merchantCode' => '154205413',
            'merchantPassword' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
            'terminal' => '100',
            'environment' => 'test',
        );

        $token = "W1p1k40011";
        $verify_token = $request->verify_token;
        $payload = $request->payload;
        $Ds_MerchantParameters = $request->Ds_MerchantParameters;


        $form = null;

        $key = $settings["merchantPassword"];
        $code = $settings["merchantCode"];
        $terminal = $settings["terminal"];
        $enviroment = $settings["environment"];
        $tradename = $settings["name"];

        $referencia = $request->referencia;

        if ($referencia == "") {

            $sig_id = Pago::max("id");
            $sig_id++;
            $sig_id = substr(date('ymdHis') . 'Id' . $sig_id, -12, 12);
            $referencia = $sig_id;
        } else {

            $referencia = $request->referencia;
        }
        //ds($id,$ref_pago,$metodo_pago,$pagado,$notificado,$estado_id);


        $reply = Pago::where('id', $request->id)->findorFail($request->id);
        $reply->id = $request->id;
        $reply->nombre = $request->nombre;
        $reply->referencia = $referencia;
        $reply->fecha_terminado = $request->fecha_terminado;
        $reply->ref_pago = $request->ref_pago;
        $reply->email = $request->email;
        $reply->telefono = $request->telefono;
        $reply->recogida = $request->recogida;
        $reply->notificado = $request->notificado;
        $reply->estado_id = $request->estado_id;
        $reply->pagado = $request->pagado;
        $reply->factura = $request->factura;
        $reply->latlng = $request->latlng;
        $reply->direccion = $request->direccion;
        $reply->metodo_pago = $request->metodo_pago;

        $reply->pagado = $request->pagado;

        /*dd($reply);*/

        $total = 0;


        $id = $reply->id;
        $ref_pago = $reply->ref_pago;
        $referencia = $referencia;
        $metodo_pago = $reply->metodo_pago;
        $pagado = $reply->pagado;
        $notificado = $reply->notificado;
        $estado_id = $reply->estado_id;
        $importe = $reply->importe;
        $recogida = $reply->recogida;
        $telefono = $reply->telefono;
        $nombre = $reply->nombre;

        $reply->save();


        $pagado = str_replace(',', ".", $pagado);
        $pagado2 = $pagado;
        $pagado = $pagado;

        $total = $pagado;


        $description = "Reserva " . $referencia . " ( " . $recogida . " ) " . $telefono;

        Redsys::setAmount($pagado);
        Redsys::setOrder($referencia);
        Redsys::setMerchantcode($code);
        Redsys::setCurrency('978');
        Redsys::setTransactiontype('0');
        Redsys::setTerminal($terminal);
        Redsys::setMethod('T');
        Redsys::setNotification(config('redsys.url_notification'));
        Redsys::setUrlOk(config('redsys.url_ok'));
        Redsys::setUrlKo(config('redsys.url_ko'));
        Redsys::setVersion('HMAC_SHA256_V1');
        Redsys::setTradeName($tradename);
        Redsys::setTitular($nombre);
        Redsys::setProductDescription($description);
        Redsys::setEnviroment($enviroment);

        $signature = Redsys::generateMerchantSignature($key);
        Redsys::setMerchantSignature($signature);

        $form = Redsys::createForm();
        ds(Redsys::executeRedirection(true));
        return view('payment.redsys', compact('form', 'nombre', 'total', 'description'));
    }

    public function validateRedsysSignature($dsSignature, $merchantParams)
    {
        $key = base64_decode(config('redsys.key'));
        $decodedMerchantParams = base64_decode($merchantParams);
        $key = base64_decode(strtr($key, '-_', '+/'));
        $generatedSignature = hash_hmac('sha256', $decodedMerchantParams, $key, true);
        $encodedSignature = base64_encode($generatedSignature);

        Log::info('Firma recibida: ' . $dsSignature);
        Log::info('Firma esperada: ' . $encodedSignature);

        return $dsSignature === $encodedSignature;
    }

    public function pdf(string $id)
    {
        $order = Pago::findOrFail($id);

        return view('pdf.order', [
            'order' => $order,
        ]);

        //return $pdf->stream("entrades-{$id}.pdf");

    }

    public function payFromPago2(Pago $pago)
    {
        $settings = array(
            'name' => 'TaxiLanz',
            'merchantCode' => '154205413',
            'merchantPassword' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
            'terminal' => '100',
            'environment' => 'test',
        );

        $token = "W1p1k40011";
        $verify_token = $pago->verify_token;
        $payload = $pago->payload;
        $Ds_MerchantParameters = $pago->Ds_MerchantParameters;

        $form = null;

        $referencia = $pago->referencia;

        if ($referencia == "") {

            $sig_id = Pago:: max("id");
            $sig_id++;
            $sig_id = substr(date('ymdHis') . 'Id' . $sig_id, -12, 12);
            $referencia = $sig_id;
        } else {

            $referencia = $pago->referencia;
        }
        //$id,$ref_pago,$metodo_pago,$pagado,$notificado,$estado_id;

        $reply = Pago::where('id', $pago->id)->findorFail($pago->id);
        $reply->id = $pago->id;
        $reply->nombre = $pago->nombre;
        $reply->referencia = $referencia;
        $reply->fecha_terminado = $pago->fecha_terminado;
        $reply->ref_pago = $pago->ref_pago;
        $reply->email = $pago->email;
        $reply->telefono = $pago->telefono;
        $reply->recogida = $pago->recogida;
        $reply->notificado = $pago->notificado;
        $reply->estado_id = $pago->estado_id;
        $reply->pagado = $pago->pagado;
        $reply->factura = $pago->factura;
        $reply->latlng = $pago->latlng;
        $reply->direccion = $pago->direccion;
        $reply->metodo_pago = $pago->metodo_pago;

        $reply->pagado = $pago->pagado;

        $id = $reply->id;
        $ref_pago = $reply->ref_pago;
        $referencia = $referencia;
        $metodo_pago = $reply->metodo_pago;
        $pagado = $reply->pagado;
        $notificado = $reply->notificado;
        $estado_id = $reply->estado_id;
        $importe = $reply->importe;
        $recogida = $reply->recogida;
        $telefono = $reply->telefono;
        $nombre = $reply->nombre;

        $reply->save();

        $pagado = str_replace(',', ".", $pagado);
        $pagado2 = $pagado;
        $pagado = $pagado;

        $total = $pagado;


        $description = "Reserva " . $referencia . " ( " . $recogida . " ) " . $telefono;

        /*Redsys::setAmount($pagado);
        Redsys::setOrder($referencia);
        Redsys::setMerchantcode($code);
        Redsys::setCurrency('978');
        Redsys::setTransactiontype('0');
        Redsys::setTerminal($terminal);
        Redsys::setMethod('T');
        Redsys::setNotification(config('redsys.url_notification'));
        Redsys::setUrlOk(config('redsys.url_ok'));
        Redsys::setUrlKo(config('redsys.url_ko'));
        Redsys::setVersion('HMAC_SHA256_V1');
        Redsys::setTradeName($tradename);
        Redsys::setTitular($nombre);
        Redsys::setProductDescription($description);
        Redsys::setEnviroment($enviroment);

        $signature = Redsys::generateMerchantSignature($key);
        Redsys::setMerchantSignature($signature);

        $form = Redsys::createForm();
        echo Redsys::executeRedirection(true);*/


        ds($reply);

        $secretKey = $settings["merchantPassword"];
        $merchantCode = $settings["merchantCode"];
        $terminal = $settings["terminal"];
        $enviroment = $settings["environment"];
        $tradename = $settings["name"];


        if ($merchantCode === '' || $secretKey === '') {
            abort(500, 'Configuración de Redsys incompleta. Define REDSYS_MERCHANT_CODE y REDSYS_SECRET_KEY en .env');
        }

        $amount = (float)($reply->importe ?? 0);
        $amountInCents = (int)round($amount * 100);
        if ($amountInCents < 1) {
            abort(422, 'Importe inválido para pago');
        }

        // ORDER: 4-12 dígitos, solo números. Usamos el ID del pago acolchado.
        $order = str_pad((string)$pago->id, 12, '0', STR_PAD_LEFT);

        $merchantParameters = [
            'DS_MERCHANT_AMOUNT' => $amountInCents,
            'DS_MERCHANT_ORDER' => $referencia,
            'DS_MERCHANT_MERCHANTCODE' => $merchantCode,
            'DS_MERCHANT_CURRENCY' => '978', // EUR
            'DS_MERCHANT_TRANSACTIONTYPE' => '0',   // Compra
            'DS_MERCHANT_TERMINAL' => $terminal,
            'DS_MERCHANT_TITULAR' => $pago->nombre ?? 'Cliente',
            'DS_MERCHANT_PRODUCTDESCRIPTION' => 'Pago Taxi ' . ($pago->referencia ?? $order),
            'DS_VERSION' => 'HMAC_SHA256_V1',
            'DS_TRADENAME' => $tradename,
            'DS_MERCHANT_ENVIROMENT' => $enviroment,
            'DS_MERCHANT_CONSUMERLANGUAGE' => '001', // Español
            'DS_MERCHANT_MERCHANTURL' => route('redsys.callback'),
            'DS_MERCHANT_URLOK' => route('redsys.ok'),
            'DS_MERCHANT_URLKO' => route('redsys.ko'),
        ];


        $parametersBase64 = base64_encode(json_encode($merchantParameters, JSON_UNESCAPED_SLASHES));

        $signature = $this->generateSignature(
            merchantParametersBase64: $parametersBase64,
            order: $order,
            secretKeyBase64: $secretKey
        );
        $signatureVersion = 'HMAC_SHA256_V1';
        $redsysUrl = (string)config('services.redsys.url', env('REDSYS_URL', 'https://sis-t.redsys.es/sis/realizarPago'));
        $redsysUrl = 'https://sis-t.redsys.es:25443/sis/realizarPago';
        ds([
            'action' => $redsysUrl,
            'signatureVersion' => $signatureVersion,
            'merchantParameters' => $parametersBase64,
            'signature' => $signature,
        ]);
        return response()->view('redsys.submit', [
            'action' => $redsysUrl,
            'signatureVersion' => $signatureVersion,
            'merchantParameters' => $parametersBase64,
            'signature' => $signature,
        ]);
    }

    public function callback(Request $request)
    {
        // TODO: Validar firma de respuesta Redsys y actualizar estado de pago
        // $paramsB64 = $request->input('Ds_MerchantParameters');
        // $signature = $request->input('Ds_Signature');
        // $version   = $request->input('Ds_SignatureVersion');

        // Debe responder 200/OK para que Redsys considere entregada la notificación

        $data = $request->all();
        $signature = $data['Ds_Signature'];
        $merchantParameters = $data['Ds_MerchantParameters'];
        $order = $data['Ds_Order'];
        $merchantCode = $data['Ds_MerchantCode'];
        $terminal = $data['Ds_Terminal'];
        $transactionType = $data['Ds_TransactionType'];
        $responseCode = $data['Ds_Response'];

        $settings = array(
            'name' => 'TaxiLanz',
            'merchantCode' => '154205413',
            'merchantPassword' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
            'terminal' => '100',
            'environment' => 'test',

        );
        $merchantCode = $settings["merchantCode"];
        $merchantPassword = $settings["merchantPassword"];

        $merchantParameters = json_decode(base64_decode($request->Ds_MerchantParameters), true);
        $signature = $request->Ds_Signature;
        $signatureVersion = $request->Ds_SignatureVersion;
        $order = $merchantParameters['DS_MERCHANT_ORDER'];
        $amount = $merchantParameters['DS_MERCHANT_AMOUNT'];
        $currency = $merchantParameters['DS_MERCHANT_CURRENCY'];
        $transactionType = $merchantParameters['DS_MERCHANT_TRANSACTIONTYPE'];
        $merchantURL = $merchantParameters['DS_MERCHANT_MERCHANTURL'];
        $merchantData = $merchantParameters['DS_MERCHANT_MERCHANTDATA'];
        ds($merchantParameters);

        return response('OK', 200);

    }

    public function ok()
    {

        return view('redsys.ok');
    }

    public function ko()
    {
        return view('redsys.ko');
    }

    /**
     * Genera la firma HMAC_SHA256_V1 de Redsys
     *
     * @param string $merchantParametersBase64 JSON de parámetros en Base64
     * @param string $order DS_MERCHANT_ORDER (4-12 dígitos)
     * @param string $secretKeyBase64 Clave secreta del comercio en Base64
     */
    private function generateSignature(string $merchantParametersBase64, string $order, string $secretKeyBase64): string
    {
        $key = base64_decode($secretKeyBase64, true);
        if ($key === false) {
            throw new \RuntimeException('La clave secreta Redsys no es un Base64 válido');
        }

        // Clave derivada: 3DES (DES-EDE3-CBC) del ORDER con IV = 0
        $iv = str_repeat("\0", 8);
        $derivedKey = openssl_encrypt(
            $order,
            'DES-EDE3-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($derivedKey === false) {
            throw new \RuntimeException('No se pudo derivar la clave 3DES para la firma Redsys');
        }

        $data = base64_decode($merchantParametersBase64, true);
        if ($data === false) {
            throw new \RuntimeException('MerchantParameters Base64 no válido');
        }

        $hmac = hash_hmac('sha256', $data, $derivedKey, true);

        return $this->base64UrlEncode($hmac);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
