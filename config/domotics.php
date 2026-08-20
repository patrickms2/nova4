<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Adaptador de dispositivos domóticos
    |--------------------------------------------------------------------------
    |
    | Valores soportados:
    |   - 'dummy': no ejecuta ninguna acción real
    |   - 'shell': ejecuta comandos configurados
    |   - 'ikea': controla luces a través del hub IKEA Dirigera
    |
    */
    'adapter' => env('DOMOTICS_ADAPTER', 'dummy'),

    /*
    |--------------------------------------------------------------------------
    | Plantillas de comandos (modo 'shell')
    |--------------------------------------------------------------------------
    |
    | Placeholders: {id}, {name}, {property_id}, {device_id}
    |
    */
    'commands' => [
        'open' => env('DOMOTICS_OPEN_COMMAND'),
        'close' => env('DOMOTICS_CLOSE_COMMAND'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de IKEA Home smart (Dirigera hub)
    |--------------------------------------------------------------------------
    */
    'ikea' => [
        'hub_ip' => env('DOMOTICS_IKEA_HUB_IP'),
        'token' => env('DOMOTICS_IKEA_TOKEN'),
        'verify_ssl' => env('DOMOTICS_IKEA_VERIFY_SSL', false),
    ],
];
