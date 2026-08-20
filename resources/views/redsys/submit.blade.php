<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Redirigiendo a la pasarela…</title>
</head>
<body>

<form class="redsys-auto-submit" id="redsys_form" name="redsys_form" action="{{ $action }}" method="post"  target="_blank" >
    <input type="hidden" name="Ds_SignatureVersion" value="{{ $signatureVersion }}">
    <input type="hidden" name="Ds_MerchantParameters" value="{{ $merchantParameters }}">
    <input type="hidden" name="Ds_Signature" value="{{ $signature }}">
    <noscript>
        <p>Vas a ser redirigido a la pasarela de pago.</p>
        <button type="submit">Continuar</button>
    </noscript>
    <input type="submit" name="btn_submit" id="btn_submit" value="Pagar"   class="btn btn-primary">

</form>

</body>
</html>
