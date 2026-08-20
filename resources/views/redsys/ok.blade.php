<?php $page = 'blogs';

use App\Models\Taxi\Pago;
use Redsys\Tpv\Tpv;
//$pago = Pago::find($pagos->id);
//ds($pago);


$merchantParams = $_GET['Ds_MerchantParameters'];
$dsSignature = $_GET['Ds_Signature'];

    $params = json_decode(base64_decode($merchantParams));

$config = array(
    'Environment' => 'test', // Puedes indicar test o real
    'MerchantCode' => '154205413',
    'Key' => 'sq7HjrUOBfKmC576ILgskD5srU870gJ7',
    'Terminal' => '100',
    'TransactionType' => '0',
    'Currency' => '978',
    'MerchantName' => 'TaxiLanz',
    'Titular' => 'TaxiLanz',
    'ConsumerLanguage' => '001',
    'SignatureVersion' => 'HMAC_SHA256_V1',
    'name' => 'TaxiLanz',
);

$TPV = new Tpv($config);

$datos = $TPV->getTransactionParameters($_GET);

// Normaliza claves a minúsculas para evitar "Undefined array key"
$datosLower = [];
foreach ($datos as $k => $v) {
    $datosLower[strtolower($k)] = $v;
}

// Obtiene el order/num pedido de forma segura (Redsys usa Ds_Order)
$dsOrder = $datosLower['ds_order'] ?? null;

// Busca el pago solo si existe el campo
$pago = null;
if ($dsOrder !== null) {
    $pago = Pago::where('referencia', $dsOrder)->first()->toArray();
}
    ?>
<div class="container py-5">
    <h1 class="h4">Pago completado</h1>
    <p>Tu pago se ha realizado correctamente. Gracias.</p>

    <!-- Botón para abrir el modal -->
    <button type="button" id="openModalBtn" class="btn envia-btn btn-sm">
        Ver detalles
    </button>
</div>



<style>
    .hidden{
        display: none !important;
    }

    .text-primary {
        color: #ce2358 !important;
    }
    .envia-btn {
        color: whitesmoke;
        background-color: #cb0101;
        font-size: 14px;
        padding: 5px 15px;
        border-top: 2px solid brown;
        border-radius: 15px;
        margin-left: 5px;
        position: relative;
    }


    div#editor {
        width: 81%;
        margin: auto;
        text-align: left;
    }

    .ss {
        background-color: red;
    }

    .form-label{
        float: left;
        font-weight: 600;
    }
    .no-padding{
        margin: 0 !important;
        padding: 0 !important;
    }

    ul.list-checked.list-checked-primary.list-py-2 {
        text-align: left;
        line-height: normal;
        vertical-align: middle;
    }

    li.list-checked-item {
        padding-top: 0 !important;
    }

    .navbar-light .navbar-brand, .navbar-light .navbar-brand:focus, .navbar-light .navbar-brand:hover {
        color: #51596c;
        color: #ffffff !important;
        font-size: 28px;
        font-weight: 600;
    }
    body {
        padding:0 !important;
    }


    pre {
        display: block !important;
        position: relative !important;
        margin-top: 0;
        overflow: visible !important;
        text-wrap: balance !important;
        margin-bottom: 15px !important;
    }

    .page-link {
        position: relative;
        display: block;
        padding: .5rem .75rem;
        font-size: 12px;
        margin-left: -1px;
        line-height: 1.25;
        color: #ce2358;
        background-color: #fff;
        border: 1px solid #dee2e6
    }


    .page-item.active .page-link {
        z-index: 3;
        color: #fff;
        font-size: 12px;
        background-color: #ce2358;
        border-color: #b53779;
    }


    .modal-header {
        background-color: #b61e1e;
        color: whitesmoke;
    }


    .modal-header > h5 {
        margin: 0px !important;
        color: whitesmoke;
        font-size: medium;
        padding: 0px !important;
        /* padding-bottom: 5px; */
        position: relative;
        width: 100% !important;
    }

    span.divider-center {
        --bs-text-opacity: 1;
        color: #a7acb2 !important;
    }

    .footer.d-flex {
        background: lightgray;
        padding: 10px;
        border-top: 2px ridge brown;
        border-top-color: ghostwhite;
        margin-top: 0px !important;
    }.text-primary {
         color: #ce2358 !important;
     }



    div#editor {
        width: 81%;
        margin: auto;
        text-align: left;
    }

    .ss {
        background-color: red;
    }

    .form-label{
        float: left;
        font-weight: 600;
    }
    .no-padding{
        margin: 0 !important;
        padding: 0 !important;
    }

    ul.list-checked.list-checked-primary.list-py-2 {
        text-align: left;
        line-height: normal;
        vertical-align: middle;
    }

    li.list-checked-item {
        padding-top: 0 !important;
    }

    .navbar-light .navbar-brand, .navbar-light .navbar-brand:focus, .navbar-light .navbar-brand:hover {
        color: #51596c;
        color: #ffffff !important;
        font-size: 28px;
        font-weight: 600;
    }
    body {
        padding:0 !important;
    }


    pre {
        display: block !important;
        position: relative !important;
        margin-top: 0;
        overflow: visible !important;
        text-wrap: balance !important;
        margin-bottom: 15px !important;
    }

    .page-link {
        position: relative;
        display: block;
        padding: .5rem .75rem;
        font-size: 12px;
        margin-left: -1px;
        line-height: 1.25;
        color: #ce2358;
        background-color: #fff;
        border: 1px solid #dee2e6
    }


    .page-item.active .page-link {
        z-index: 3;
        color: #fff;
        font-size: 12px;
        background-color: #ce2358;
        border-color: #b53779;
    }


    .modal-header {
        background-color: #b61e1e;
        color: whitesmoke;
    }


    .modal-header > h5 {
        margin: 0px !important;
        color: whitesmoke;
        font-size: medium;
        padding: 0px !important;
        /* padding-bottom: 5px; */
        position: relative;
        width: 100% !important;
    }

    span.divider-center {
        --bs-text-opacity: 1;
        color: #a7acb2 !important;
    }

    .footer.d-flex {
        background: lightgray;
        padding: 10px;
        border-top-style: groove;
        border-top-color: ghostwhite;
        margin-top: 0px !important;
    }
    div#tabla_taxis_length {
        display: inline-block;
    }
    div#tabla_taxis_filter {
        max-width: min-content;
        display: flex
    ;
        position: relative;
        float: inline-end;
    }
    .w-115{
        width: 115px !important;

    }
    .modal-backdrop {
        --bs-backdrop-zindex: 1089;
        --bs-backdrop-bg: #4b465c;
        --bs-backdrop-opacity: .5;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 10;
        width: 100vw;
        height: 100vh;
        background-color: var(--bs-backdrop-bg);
    }

    .bg-label-success {
        background-color: #deede6 !important;
        color: #407f5c !important;
        font-size: 14px !important;
    }

    .bg-label-primary {
        font-size: 14px !important;
        background-color: #fbdddd8c !important;
        color: #e52c2c !important;
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<div class="modal show  relative top-0 left-0 right-0 z-50 items-center justify-center p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)]  " id="editUserModal" tabindex="-1" aria-labelledby="offcanvasAddUserLabel" aria-hidden="true" style="display: none;">
    <div class="modal-md modal-dialog">
        <div class="modal-content">
            <!-- Modal content -->
            <form id="editaForm" name="editaForm" method="POST" class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700 ajaxform">
                @csrf
                @method("POST")

                <!-- Modal body -->
                <div class="modal-body row">
                    <div id="referencias" class="fi-sc  fi-sc-has-gap fi-grid sm:fi-grid-cols  fi-section-content grid-cols-4">
                        <div class="fi-grid">
                            <div class="fi-grid-col">
                                <label for="referencia" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label form-label">Ref. Servicio</label>
                                <input type="text"   class="shadow-xs bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="referencia" id="referencia" placeholder="" aria-label="" value="{{ $pago['referencia'] }}" >
                            </div>
                        </div>
                        <div class="fi-grid">
                            <div class="fi-grid-col">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label form-label" for="contactsFormFirstName">Id</label>
                                <input type="text" readonly="true" class="shadow-xs bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 d dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="id" id="id" placeholder="id" aria-label="" value="{{ $pago['id'] }}">
                            </div>
                        </div>
                        <div class="fi-grid">
                            <div class="fi-grid-col">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormFirstName">Ref. Pago</label>
                                <input type="text" readonly="true" class="shadow-xs bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="ref_pago" id="ref_pago" placeholder="" aria-label="" value=" {{ $pago['ref_pago']  }}
                  ">
                            </div>
                        </div>

                    </div>
                    <div class="row mb-0">
                        <div class="col-sm-3 mb-4 mb-sm-0">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormFirstName">Lugar</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 textprimary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="recogida" id="recogida" placeholder="Famara" aria-label="Famara" value="{{ $pago['recogida'] }}">
                                <div class="hidden">
                                    <input type="text" class="inline-block shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600  p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500 text-primary" name="direccion" id="direccion" placeholder="direccion" aria-label="Famara" value="{{ $pago['direccion'] }}">
                                    <input type="text" class="inline-block shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600  p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500 text-primary" name="latlng" id="latlng" placeholder="latlng" aria-label="latlng" value="29.0076812,-13.4853378">

                                    <button type="button" id="btndireccion" class="inline-block px-6 py-3 text-sm font-semibold text-white bg-red-500 rounded-lg hover:bg-blue-600 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 transition-all"  aria-label="Recargar">R</button>
                                    <button type="button" id="btndireccion2" class="inline-block px-6 py-3 text-sm font-semibold text-white bg-red-500 rounded-lg hover:bg-blue-600 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 transition-all"  aria-label="Recargar">C</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormLastName">Imp. Total</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 textprimary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="importe" id="importe" placeholder="125" aria-label="125" value="{{ $pago['importe'] }}">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormLastName">Imp. Pago</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="pagado" id="pagado" placeholder="125" aria-label="125" value="{{ $pago['pagado'] }}">
                            </div>
                        </div>

                    </div>

                    <div class="row row mb-0">
                        <div class="col-sm-6 mb-4 mb-sm-0">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormFirstName">Nombre</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="nombre" id="nombre" placeholder="Patrick" aria-label="Patrick" value="{{ $pago['nombre'] }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormLasttName">Teléfono</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="telefono" id="telefono" placeholder="34646426442" aria-label="34646426442" value="{{ $pago['telefono'] }}" data-listener-added_3f3820af="true">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormLasttName">Método Pago</label>
                                <select class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="metodo_pago" id="metodo_pago" placeholder="34646426442" aria-label="34646426442" value="{{ $pago['metodo_pago'] }}">
                                    <option value="C">Tarjeta Crédito</option>
                                    <option value="z">Bizum</option>
                                    <option value="xpay">ApplePay</option>
                                </select>
                            </div>
                        </div><div class="col-sm-6">

                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormWorkEmail">Email</label>
                                <input type="text" class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="email" id="email" placeholder="patrickms@novagestion.eu" aria-label="patrickms@novagestion.eu" value="{{ $pago['email'] }}">
                            </div>
                        </div>
                        <div class="col-sm-3 mb-4 mb-sm-1">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormFirstName">Notificado</label>

                                <select class="shadow-xs bg-gray-50 border border-gray-300 text-primary text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="notificado" id="notificado" placeholder="34646426442" aria-label="34646426442" value="{{ $pago['notificado'] }}">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                </select></div>
                        </div>
                        <div class="col-sm-3">
                            <div class="mb-2">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white form-label" for="contactsFormFirstName">Estado</label>

                                <select class="shadow-xs bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5  dark:border-gray-500 dark:placeholder-gray-400 dark:text-gray dark:focus:ring-blue-500 dark:focus:border-blue-500" name="estado_id" id="estado_id" placeholder="estado_id" aria-label="estado_id" value="{{ $pago['estado_id'] }}">
                                    @php
                                        use \App\Enums\PagoEstado;
                                        echo PagoEstado::select();
                                    @endphp
                                </select></div>
                        </div>
                    </div>


                    <div class="footer d-flex">
                        <input type="hidden" id="urlPago" name="urlPago" value="">
                        <input type="hidden" id="factura" name="factura" value="0">
                        <input type="hidden" id="nuevo" name="nuevo" value="0">

                        <input type="hidden" id="token" name="token" value="">
                        <input type="hidden" id="sig_id" name="sig_id" value="">
                        <input type="hidden" id="sig_id2" name="sig_id2" value="">
                        <input type="hidden" id="fecha_hora" name="fecha_hora" value="">
                        <div  id="map" class="list-checked-item mb-4 pb-1 d-flex flex-column justify-content-between align-items-center"></div>

                        <a id="enviarBtn2" type="button" class="btn envia-btn btn-sm action-confirm " href="/admin/pagos"  ><< Volver</a>

                    </div>

                    <ul class=" hidden list-checked small list-checked-primary list-py-2">
                        <li  id="resultado" class="list-checked-item mb-4 pb-1 d-flex flex-column justify-content-between align-items-center">

                        </li>
                    </ul>
                    <div class="col-sm-12 hidden ">
                        <div class="p-0" style="">
                            <div class="form-control" name="respuesta" id="respuesta" style='border: 1px;font-size: 12px;text-align:left;margin-right: 0 !important;margin-top: 0px;display: inline-block;width: 100% !important;padding: 0 !important;height: 0px;overflow-y: overlay;overflow-x: hidden;'></div>
                        </div>
                        <div class="">

                        </div>
                    </div>
                </div>

        </div>


</div>
</div>
<script src="https://taxi_api5.test/assets2/js/jquery-3.7.1.min.js" ></script>
<script>
    // Toggle del modal sin depender de Bootstrap
    (function() {
        const modal = document.getElementById('editUserModal');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');

        function openModal() {
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            // Backdrop simple
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }

        function closeModal() {
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        }

        if (openBtn) openBtn.addEventListener('click', openModal);
        if (closeBtn) closeBtn.addEventListener('click', closeModal);

        // Cerrar al hacer click fuera del contenido
        modal?.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        // Cerrar con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    })();
    </script>





