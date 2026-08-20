<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'ollama' => [

        'url' => env('OLLAMA_URL'),

        'model' => env('OLLAMA_MODEL', 'qwen3:8b'),

    ],
    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'redsys' => [
        'endpoint' => env('REDSYS_ENDPOINT', env('REDSYS_URL', 'https://sis-t.redsys.es:25443/sis/realizarPago')),
        'merchant_code' => env('REDSYS_MERCHANT_CODE'),
        'merchant_name' => env('REDSYS_MERCHANT_NAME', 'TaxiLanz'),
        'environment' => env('REDSYS_ENVIRONMENT', 'test'),
        'terminal' => env('REDSYS_TERMINAL', '100'),
        'secret_key_base64' => env('REDSYS_KEY'),
        'currency' => env('REDSYS_CURRENCY', '978'),
        'transaction_type' => env('REDSYS_TRANSACTION_TYPE', '0'),
    ],
    'taxisns' => [
        'webhook_token' => env('TAXISNS_WEBHOOK_TOKEN', 'taxisns-webhook-secret-2026'),
    ],

    'auriga' => [
        'endpoint' => env('AURIGA_ENDPOINT', 'https://api.auriga.example.com'),
        'api_key' => env('AURIGA_API_KEY'),
    ],

    'taxilanz_woocommerce' => [
        'enabled' => (bool) env('TAXILANZ_WOO_CREATE_ORDERS', false),
        'endpoint' => env('TAXILANZ_ENDPOINT_URL', 'https://taxilanzwp7.test'),
        'route_booking_url' => env('TAXILANZ_ROUTE_BOOKING_URL', 'https://taxilanz.com/rutas/ruta-redsys/'),
        'consumer_key' => env('TAXILANZ_WOO_REST_API_CLIENT'),
        'consumer_secret' => env('TAXILANZ_WOO_REST_API_CLIENT_SECRET'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'transcribe_model' => env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-transcribe'),
    ],

    'staff_api' => [
        'url' => env('STAFF_API_URL', env('APP_URL', 'http://localhost')),
    ],

    'nova' => [
        'webhook_token' => env('NOVA_WEBHOOK_TOKEN'),
        'whatsapp_verify_token' => env('NOVA_WHATSAPP_VERIFY_TOKEN', 'W1p1k40011Aa%'),
        'whatsapp_access_token' => env('NOVA_WHATSAPP_ACCESS_TOKEN'),
        'whatsapp_phone_number_id' => env('NOVA_WHATSAPP_PHONE_NUMBER_ID'),
        'whatsapp_business_account_id' => env('NOVA_WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'meta_business_id' => env('NOVA_META_BUSINESS_ID'),
        'meta_app_id' => env('NOVA_META_APP_ID'),
        'meta_graph_version' => env('NOVA_META_GRAPH_VERSION', 'v22.0'),
        'whatsapp_debounce_ms' => (int) env('NOVA_WHATSAPP_DEBOUNCE_MS', 1800),
        'audio_transcription_model' => env('NOVA_AUDIO_TRANSCRIPTION_MODEL', env('OPENAI_TRANSCRIBE_MODEL', 'gpt-4o-transcribe')),
        'audio_transcription_language' => env('NOVA_AUDIO_TRANSCRIPTION_LANGUAGE', 'es'),
        'nova_url' => env('NOVA_URL', 'https://novahubmcp.test'),
        'sirvo_endpoint_url' => env('SIRVO_ENDPOINT_URL', 'http://192.168.1.42:3000'),
        'lageria_endpoint_url' => env('LAGERIA_ENDPOINT_URL', 'https://lageriawp.test'),
        'lanzaloe_endpoint_url' => env('LANZALOE_ENDPOINT_URL', 'https://lanzaloe.novagestion.eu'),
        'taxilanz_endpoint_url' => env('TAXILANZ_ENDPOINT_URL', 'https://taxilanzwp.test'),
        'taxilanz_hoteles_endpoint_url' => env('TAXILANZ_HOTELES_ENDPOINT_URL', 'https://taxilanzhrnew.test/api/mcp'),
    ],

    'clickup' => [
        'api_token' => env('CLICKUP_API_TOKEN'),
        'workspace_id' => env('CLICKUP_WORKSPACE_ID'),
        'default_list_id' => env('CLICKUP_DEFAULT_LIST_ID'),
    ],

    'sirvo' => [
        'default_restaurant_id' => env('SIRVO_DEFAULT_RESTAURANT_ID'),
        'default_customer_name' => env('SIRVO_DEFAULT_CUSTOMER_NAME', 'Cliente WhatsApp Nova'),
    ],
    'geoapify' => [
        'geocoding' => env('GEOAPIFY_GEOCODING_KEY'),
        'routing' => env('GEOAPIFY_ROUTING_KEY'),
    ],

    'przelewy24' => [
        'merchant_id' => env('PRZELEWY24_MERCHANT_ID'),
        'pos_id' => env('PRZELEWY24_POS_ID'),
        'crc' => env('PRZELEWY24_CRC'),
        'api_key' => env('PRZELEWY24_API_KEY'),
        'sandbox' => env('PRZELEWY24_SANDBOX', true),
    ],

    'lanzaloe' => [
        'base_url' => env('LANZALOE_BASE_URL', env('NOVA_LANZALOE_MAGENTO_BASE_URL', 'https://lanzaloe.com')),
        'api_token' => env('LANZALOE_API_TOKEN', env('NOVA_LANZALOE_MAGENTO_TOKEN')),
        'store_url' => env('LANZALOE_STORE_URL', env('NOVA_LANZALOE_MAGENTO_STORE_URL', 'https://www.lanzaloe.com/es/rest/es/V1')),
        'store_code' => env('LANZALOE_STORE_CODE', 'default'),
        'timeout' => env('LANZALOE_TIMEOUT', 30),
        'enabled' => env('LANZALOE_API_ENABLED', true),
        'use_custom_order_endpoint' => env('NOVA_LANZALOE_USE_CUSTOM_ORDER_ENDPOINT', false),
    ],
    'clickup' => [
        'api_token' => env('CLICKUP_API_TOKEN'),
        'list_id' => env('CLICKUP_LIST_ID', '901219870546'), // para tareas
        'folder_id' => env('CLICKUP_FOLDER_ID', '901212619554'), // para notas
        'workspace_id' => env('CLICKUP_WORKSPACE_ID', '90121921719'),
        'space_id' => env('CLICKUP_SPACE_ID', '90128510665'), // para notas
        'team_id' => env('CLICKUP_TEAM_ID'),
    ],
];
