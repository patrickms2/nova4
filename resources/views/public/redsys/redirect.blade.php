<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecting to payment...</title>
</head>
<body>
    <form class="redsys-auto-submit" id="redsys_form" name="redsys_form" action="{{ $endpoint }}" method="POST">
        {!! $formHiddens !!}
        <noscript>
            <button type="submit">Continuar con el pago</button>
        </noscript>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('redsys_form').submit();
        });
    </script>
</body>
</html>

