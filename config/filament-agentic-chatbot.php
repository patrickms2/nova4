<?php
use Heiner\FilamentAgenticChatbot\Channels\Rendering\SlackChannelMessageRenderer;
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\ApiConnector;
use Heiner\FilamentAgenticChatbot\Models\Bot;
use Heiner\FilamentAgenticChatbot\Models\BotAccessToken;
use Heiner\FilamentAgenticChatbot\Models\BotConversation;
use Heiner\FilamentAgenticChatbot\Models\BotMessage;
use Heiner\FilamentAgenticChatbot\Models\BotSubmission;
use Heiner\FilamentAgenticChatbot\Models\BotUsageEvent;
use Heiner\FilamentAgenticChatbot\Models\ChannelConnection;
use Heiner\FilamentAgenticChatbot\Models\ChannelDeliveryEvent;
use Heiner\FilamentAgenticChatbot\Models\ChannelThread;
use Heiner\FilamentAgenticChatbot\Models\KnowledgeChunk;
use Heiner\FilamentAgenticChatbot\Models\KnowledgeDocument;
use Heiner\FilamentAgenticChatbot\Models\KnowledgeSource;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Support\IncidentManagementDataResources;
use Heiner\FilamentAgenticChatbot\Channels\Rendering\AbstractChannelMessageRenderer;



return [
    'eloquent_models' => [
        'bot' => Bot::class,
        'source' => KnowledgeSource::class,
        'document' => KnowledgeDocument::class,
        'chunk' => KnowledgeChunk::class,
        'conversation' => BotConversation::class,
        'message' => BotMessage::class,
        'api_connector' => ApiConnector::class,
        'bot_access_token' => BotAccessToken::class,
        'bot_usage_event' => BotUsageEvent::class,
        'channel_connection' => ChannelConnection::class,
        'channel_thread' => ChannelThread::class,
        'channel_delivery_event' => ChannelDeliveryEvent::class,
    ],
    'database' => [
        'connection' => env('RAG_DB_CONNECTION'),
        'driver' => env('RAG_DB_DRIVER', 'pgsql'),
        'url' => env('RAG_DB_URL'),
        'host' => env('RAG_DB_HOST', '127.0.0.1'),
        'port' => env('RAG_DB_PORT', '5432'),
        'database' => env('RAG_DB_DATABASE', 'filament_agentic_chatbot'),
        'username' => env('RAG_DB_USERNAME', 'postgres'),
        'password' => env('RAG_DB_PASSWORD', ''),
        'charset' => env('RAG_DB_CHARSET', 'utf8'),
        'schema' => env('RAG_DB_SCHEMA', 'public'),
        'sslmode' => env('RAG_DB_SSLMODE', 'prefer'),
    ],
    'models' => [
        'embedding' => env('RAG_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'chat' => env('RAG_CHAT_MODEL', 'gemini-2.5-flash-lite'),
    ],
    'providers' => [
        // Chat provider IDs supported by the bot UI include:
        // gemini, openai, anthropic, xai, openrouter, deepseek, groq,
        // mistral, ollama, azure, and openai_compatible.
        // Embedding provider IDs supported by setup checks include:
        // gemini, openai, openrouter, mistral, ollama, azure, cohere,
        // jina, and voyageai.
        'embedding' => env('RAG_EMBEDDING_PROVIDER', 'gemini'),
        'chat' => env('RAG_CHAT_PROVIDER', 'gemini'),
    ],
    'openai_compatible' => [
        'driver' => env('RAG_OPENAI_COMPATIBLE_DRIVER', 'openrouter'),
        'base_url' => env('RAG_OPENAI_COMPATIBLE_BASE_URL'),
        'api_key' => env('RAG_OPENAI_COMPATIBLE_API_KEY'),
    ],
    'chunking' => [
        'size' => 1200,
        'overlap' => 200,
        'size_tokens' => env('RAG_CHUNK_SIZE_TOKENS'),
        'overlap_tokens' => env('RAG_CHUNK_OVERLAP_TOKENS'),
        'use_estimated_tokens' => env('RAG_CHUNK_USE_ESTIMATED_TOKENS', false),
        'tokenizer_encoding' => env('RAG_CHUNK_TOKENIZER_ENCODING', 'cl100k_base'),
        'embedding_batch_size' => 20,
        'max_chunks' => 500,
    ],
    'retrieval' => [
        'top_k' => 6,
        'min_similarity' => 0.65,
        'max_context_chars' => 12000,
        'hybrid' => [
            'enabled' => (bool) env('RAG_RETRIEVAL_HYBRID_ENABLED', true),
            'lexical_candidate_limit' => (int) env('RAG_RETRIEVAL_LEXICAL_CANDIDATE_LIMIT', 200),
            'lexical_weight' => (float) env('RAG_RETRIEVAL_LEXICAL_WEIGHT', 0.16),
            'lexical_min_token_length' => (int) env('RAG_RETRIEVAL_LEXICAL_MIN_TOKEN_LENGTH', 3),
            'lexical_max_query_tokens' => (int) env('RAG_RETRIEVAL_LEXICAL_MAX_QUERY_TOKENS', 8),
            'lexical_stopwords' => array_values(array_filter(array_map(
                static fn ($token) => trim((string) $token),
                explode(',', (string) env('RAG_RETRIEVAL_LEXICAL_STOPWORDS', 'the,and,for,with,that,this,from,you,your,are,ist,und,wer,was,wie,der,die,das,ein,eine,oder,den,dem,des,von,mit,auf,zu,im,in'))
            ), static fn (string $token): bool => $token !== '')),
        ],
    ],
    'vector' => [
        'backend' => env('RAG_VECTOR_BACKEND', 'pgvector'),
        'chroma' => [
            'url' => env('RAG_CHROMA_URL', 'http://127.0.0.1:8001'),
            'token' => env('RAG_CHROMA_TOKEN'),
            'tenant' => env('RAG_CHROMA_TENANT', 'default_tenant'),
            'database' => env('RAG_CHROMA_DATABASE', 'default_database'),
            'collection' => env('RAG_CHROMA_COLLECTION', 'filament-agentic-chatbot'),
            'timeout' => (int) env('RAG_CHROMA_TIMEOUT', 10),
        ],
        // Pinecone support is planned for a future release.
        // See CHANGELOG.md for the roadmap.
    ],
    'chat' => [
        'history_messages' => (int) env('RAG_CHAT_HISTORY_MESSAGES', 20),
        // The parent agent is the default runtime. It orchestrates memory,
        // knowledge search, workflow execution, and direct answers. Disable
        // only when you need the legacy direct RAG compatibility path.
        'parent_agent' => [
            'enabled' => (bool) env('RAG_PARENT_AGENT_ENABLED', true),
            'workflow_tool' => [
                'enabled' => (bool) env('RAG_PARENT_AGENT_WORKFLOW_TOOL_ENABLED', true),
            ],
        ],
        'session_memory' => [
            'enabled' => (bool) env('RAG_CHAT_SESSION_MEMORY_ENABLED', true),
            'history_messages' => (int) env('RAG_CHAT_SESSION_MEMORY_HISTORY_MESSAGES', env('RAG_CHAT_HISTORY_MESSAGES', 20)),
            'state' => [
                'enabled' => (bool) env('RAG_CHAT_SESSION_STATE_MEMORY_ENABLED', true),
                'namespace' => env('RAG_CHAT_SESSION_STATE_MEMORY_NAMESPACE', 'chat_session'),
                'max_value_length' => (int) env('RAG_CHAT_SESSION_STATE_MEMORY_MAX_VALUE_LENGTH', 1000),
                'topic_stopwords' => array_values(array_filter(array_map(
                    static fn ($word) => trim((string) $word),
                    explode(',', (string) env('RAG_CHAT_SESSION_STATE_MEMORY_TOPIC_STOPWORDS', ''))
                ), static fn (string $word): bool => $word !== '')),
            ],
        ],
        'default_response_format' => env('RAG_CHAT_RESPONSE_FORMAT', 'markdown'),
    ],
    'usage' => [
        'store_events' => (bool) env('RAG_STORE_USAGE_EVENTS', true),
        'default_max_input_tokens' => env('RAG_DEFAULT_MAX_INPUT_TOKENS'),
        'default_max_output_tokens' => env('RAG_DEFAULT_MAX_OUTPUT_TOKENS'),
        'default_monthly_token_budget' => env('RAG_DEFAULT_MONTHLY_TOKEN_BUDGET'),
        'default_monthly_cost_budget_cents' => env('RAG_DEFAULT_MONTHLY_COST_BUDGET_CENTS'),
        'currency_code' => env('RAG_USAGE_CURRENCY_CODE', 'USD'),
        'currency_symbol' => env('RAG_USAGE_CURRENCY_SYMBOL', '$'),
        'currency_symbol_position' => env('RAG_USAGE_CURRENCY_SYMBOL_POSITION', 'before'),
        'cost_display_decimals' => (int) env('RAG_USAGE_COST_DECIMALS', 4),
        'cost_compact_display_decimals' => (int) env('RAG_USAGE_COST_COMPACT_DECIMALS', 2),
        'cost_minor_units_per_unit' => (int) env('RAG_USAGE_COST_MINOR_UNITS_PER_UNIT', 100),
        'cost_minor_unit_label' => env('RAG_USAGE_COST_MINOR_UNIT_LABEL', 'cents'),
        /*
         * Optional price map used only for estimated cost logging and cost budgets.
         * Prices are intentionally not hard-coded because provider pricing changes.
         *
         * Example:
         * 'pricing' => [
         *     'openai:gpt-4o-mini' => [
         *         'input_minor_units_per_million' => 15,
         *         'output_minor_units_per_million' => 60,
         *     ],
         * ],
         */
        'pricing' => [],
    ],
    'context' => [
        'default_area' => env('RAG_CONTEXT_DEFAULT_AREA', 'public'),
        'allowed_areas' => array_values(array_filter(array_map(
            static fn ($area) => trim((string) $area),
            explode(',', (string) env('RAG_CONTEXT_ALLOWED_AREAS', ''))
        ))),
        'max_area_length' => 64,
        'authorization' => [
            'enabled' => (bool) env('RAG_CONTEXT_AUTHORIZATION_ENABLED', true),
            'require_auth_for_non_public' => (bool) env('RAG_CONTEXT_REQUIRE_AUTH_FOR_NON_PUBLIC', true),
            'public_areas' => array_values(array_filter(array_map(
                static fn ($area) => trim((string) $area),
                explode(',', (string) env('RAG_CONTEXT_PUBLIC_AREAS', 'public'))
            ))),
            'guards' => array_values(array_filter(array_map(
                static fn ($guard) => trim((string) $guard),
                explode(',', (string) env('RAG_CONTEXT_AUTH_GUARDS', 'web,sanctum,api'))
            ))),
            'area_abilities' => array_filter([
                'member' => trim((string) env('RAG_CONTEXT_MEMBER_ABILITY', '')),
                'admin' => trim((string) env('RAG_CONTEXT_ADMIN_ABILITY', '')),
            ], static fn ($ability): bool => $ability !== ''),
        ],
    ],
    'api' => [
        'prefix' => env('RAG_API_PREFIX', 'api/filament-agentic-chatbot'),
        'middleware' => ['api'],
        'include_session_auth_context' => (bool) env('RAG_API_INCLUDE_SESSION_AUTH_CONTEXT', true),
        'session_middleware' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
        ],
        'rate_limiter' => env('RAG_RATE_LIMITER', 'rag-chat'),
        'max_requests_per_minute' => (int) env('RAG_MAX_REQUESTS_PER_MINUTE', 40),
        'max_requests_per_minute_per_ip' => (int) env('RAG_MAX_REQUESTS_PER_MINUTE_PER_IP', 120),
        'max_execution_time' => (int) env('RAG_CHAT_MAX_EXECUTION_TIME', 120),
    ],
    'bot_access_tokens' => [
        /*
         * Optional owner model allow-list for the admin UI.
         *
         * The package stores nullable owner_type / owner_id metadata only. It
         * does not create users, teams, tenants, or authorization policies.
         *
         * Example:
         * 'owner_types' => [
         *     'user' => \App\Models\User::class,
         *     'team' => \App\Models\Team::class,
         * ],
         */
        'owner_types' => [],
        'default_channel' => env('RAG_BOT_ACCESS_TOKEN_DEFAULT_CHANNEL', 'widget'),
        'channels' => [
            'api' => 'API',
            'widget' => 'Web Widget Bridge',
            'telegram' => 'Telegram',
            'slack' => 'Slack',
            'mobile' => 'Mobile App',
            'backend' => 'Backend Job',
            'custom' => 'Custom',
        ],
    ],
    'channels' => [
        /*
         * Channels are first-class package integrations. The host app only
         * supplies credentials, webhook URLs, and optional config overrides.
         */
        'rate_limiter' => env('RAG_CHANNELS_RATE_LIMITER', 'rag-channels'),
        'max_webhook_requests_per_minute' => (int) env('RAG_CHANNELS_MAX_WEBHOOK_REQUESTS_PER_MINUTE', 120),
        'max_webhook_requests_per_minute_per_ip' => (int) env('RAG_CHANNELS_MAX_WEBHOOK_REQUESTS_PER_MINUTE_PER_IP', 240),
        'require_webhook_verification' => env('RAG_CHANNELS_REQUIRE_WEBHOOK_VERIFICATION', env('APP_ENV') === 'production'),
        'store_raw_webhook_payloads' => (bool) env('RAG_CHANNELS_STORE_RAW_WEBHOOK_PAYLOADS', false),
        'max_inbound_message_length' => (int) env('RAG_CHANNELS_MAX_INBOUND_MESSAGE_LENGTH', 2000),
        'message_too_long_reply' => env('RAG_CHANNELS_MESSAGE_TOO_LONG_REPLY', 'This message is too long. Please send a shorter message.'),
        'empty_reply_message' => env('RAG_CHANNELS_EMPTY_REPLY_MESSAGE', ''),
        'error_reply_message' => env('RAG_CHANNELS_ERROR_REPLY_MESSAGE', 'The bot could not process this message. Please try again later.'),
        'processing_timeout_seconds' => (int) env('RAG_CHANNELS_PROCESSING_TIMEOUT_SECONDS', 300),
        'webhook_base_url' => env('RAG_CHANNELS_WEBHOOK_BASE_URL', ''),
        'queue' => [
            'connection' => env('RAG_CHANNELS_QUEUE_CONNECTION', ''),
            'queue' => env('RAG_CHANNELS_QUEUE', ''),
        ],
        'slack' => [
            'default_response_mode' => env('RAG_CHANNELS_SLACK_RESPONSE_MODE', 'in_channel'),
            'use_threads' => (bool) env('RAG_CHANNELS_SLACK_USE_THREADS', true),
            'accept_thread_replies_to_bot_messages' => (bool) env('RAG_CHANNELS_SLACK_ACCEPT_THREAD_REPLIES_TO_BOT_MESSAGES', true),
        ],
        'activity' => [
            'enabled' => (bool) env('RAG_CHANNELS_ACTIVITY_ENABLED', true),
            'locale' => env('RAG_CHANNELS_ACTIVITY_LOCALE', ''),
            'texts' => [
                'placeholder' => [
                    'en' => env('RAG_CHANNELS_ACTIVITY_PLACEHOLDER_TEXT_EN', ''),
                    'de' => env('RAG_CHANNELS_ACTIVITY_PLACEHOLDER_TEXT_DE', ''),
                ],
            ],
            'telegram' => [
                'mode' => env('RAG_CHANNELS_TELEGRAM_ACTIVITY_MODE', 'native_typing'),
                'action' => env('RAG_CHANNELS_TELEGRAM_ACTIVITY_ACTION', 'typing'),
                'pulse_interval_seconds' => (int) env('RAG_CHANNELS_TELEGRAM_ACTIVITY_PULSE_INTERVAL_SECONDS', 4),
                'pulse_max_seconds' => (int) env('RAG_CHANNELS_TELEGRAM_ACTIVITY_PULSE_MAX_SECONDS', 240),
            ],
            'slack' => [
                'mode' => env('RAG_CHANNELS_SLACK_ACTIVITY_MODE', 'placeholder'),
                'placeholder_text' => env('RAG_CHANNELS_SLACK_ACTIVITY_PLACEHOLDER_TEXT', 'Working on it...'),
                'placeholder_texts' => [
                    'en' => env('RAG_CHANNELS_SLACK_ACTIVITY_PLACEHOLDER_TEXT_EN', ''),
                    'de' => env('RAG_CHANNELS_SLACK_ACTIVITY_PLACEHOLDER_TEXT_DE', ''),
                ],
                'immediate_response' => (bool) env('RAG_CHANNELS_SLACK_ACTIVITY_IMMEDIATE_RESPONSE', false),
                'immediate_response_type' => env('RAG_CHANNELS_SLACK_ACTIVITY_IMMEDIATE_RESPONSE_TYPE', 'ephemeral'),
                'update_final_message' => (bool) env('RAG_CHANNELS_SLACK_ACTIVITY_UPDATE_FINAL_MESSAGE', true),
                'ephemeral_placeholder' => (bool) env('RAG_CHANNELS_SLACK_ACTIVITY_EPHEMERAL_PLACEHOLDER', false),
                'delete_placeholder_on_fallback' => (bool) env('RAG_CHANNELS_SLACK_ACTIVITY_DELETE_PLACEHOLDER_ON_FALLBACK', true),
                'update_text_max_length' => (int) env('RAG_CHANNELS_SLACK_ACTIVITY_UPDATE_TEXT_MAX_LENGTH', 4000),
            ],
            'indicators' => [
                // Override provider => ChannelActivityIndicator::class here when needed.
            ],
        ],
        'presentation' => [
            'default_mode' => env('RAG_CHANNELS_PRESENTATION_MODE', 'text'),
            'options_heading' => env('RAG_CHANNELS_TEXT_OPTIONS_HEADING', 'Next steps:'),
            'options_instruction' => env('RAG_CHANNELS_TEXT_OPTIONS_INSTRUCTION', 'Reply with the number, the label, or continue in your own words.'),
            'sources_heading' => env('RAG_CHANNELS_TEXT_SOURCES_HEADING', 'Sources:'),
            'telegram' => [
                'mode' => env('RAG_CHANNELS_TELEGRAM_PRESENTATION_MODE', env('RAG_CHANNELS_PRESENTATION_MODE', 'text')),
                'native_buttons' => (bool) env('RAG_CHANNELS_TELEGRAM_NATIVE_BUTTONS', false),
                'native_images' => (bool) env('RAG_CHANNELS_TELEGRAM_NATIVE_IMAGES', true),
            ],
            'slack' => [
                'mode' => env('RAG_CHANNELS_SLACK_PRESENTATION_MODE', env('RAG_CHANNELS_PRESENTATION_MODE', 'text')),
                'native_buttons' => (bool) env('RAG_CHANNELS_SLACK_NATIVE_BUTTONS', false),
                'native_blocks' => (bool) env('RAG_CHANNELS_SLACK_NATIVE_BLOCKS', false),
                'native_images' => (bool) env('RAG_CHANNELS_SLACK_NATIVE_IMAGES', true),
            ],
        ],
        'drivers' => [
            // Override provider => ChannelDriver::class here when needed.
        ],
        'renderers' => [
            // Override provider => ChannelMessageRenderer::class here when needed.
        ],
    ],
    'network' => [
        'allow_private_request_urls' => (bool) env('RAG_ALLOW_PRIVATE_REQUEST_URLS', false),
    ],
    'ingestion' => [
        'allow_private_network_urls' => (bool) env('RAG_ALLOW_PRIVATE_NETWORK_URLS', false),
        'allow_sync_actions' => env('RAG_ALLOW_SYNC_ACTIONS'),
        'connection' => env('RAG_INGESTION_QUEUE_CONNECTION', ''),
        'queue' => env('RAG_INGESTION_QUEUE', ''),
        'stale_pending_after_seconds' => (int) env('RAG_INGESTION_STALE_PENDING_AFTER_SECONDS', 120),
        'stale_processing_after_seconds' => (int) env('RAG_INGESTION_STALE_PROCESSING_AFTER_SECONDS', 600),
    ],
    'widget' => [
        'enabled_in_panel' => env('RAG_WIDGET_ENABLED_IN_PANEL', false),
        'render_hook' => env('RAG_WIDGET_RENDER_HOOK', 'panels::body.end'),
        'bot_public_id' => env('RAG_WIDGET_BOT_PUBLIC_ID'),
        'default_title' => env('RAG_WIDGET_TITLE', 'Assistant'),
        'default_subtitle' => env('RAG_WIDGET_SUBTITLE', 'Always here to help'),
        'default_welcome_message' => env('RAG_WIDGET_WELCOME_MESSAGE', 'How can I help?'),
        'default_input_placeholder' => env('RAG_WIDGET_INPUT_PLACEHOLDER', 'Type a message...'),
        'script_route' => env('RAG_WIDGET_SCRIPT_ROUTE', 'filament-agentic-chatbot/widget'),
        'default_position' => env('RAG_WIDGET_DEFAULT_POSITION', 'right'),
        'default_accent_color' => env('RAG_WIDGET_DEFAULT_ACCENT_COLOR', '#d97706'),
        'default_template' => env('RAG_WIDGET_DEFAULT_TEMPLATE', 'clean'),
        'default_font_preset' => env('RAG_WIDGET_DEFAULT_FONT_PRESET', 'modern-sans'),
        'default_compact_mode' => (bool) env('RAG_WIDGET_DEFAULT_COMPACT_MODE', false),
        'default_show_sources' => (bool) env('RAG_WIDGET_DEFAULT_SHOW_SOURCES', true),
        'default_language' => env('RAG_WIDGET_DEFAULT_LANGUAGE', 'en'),
        'default_area' => env('RAG_WIDGET_DEFAULT_AREA', env('RAG_CONTEXT_DEFAULT_AREA', 'public')),
        'signing' => [
            'enabled' => (bool) env('RAG_WIDGET_SIGNING_ENABLED', true),
            'key' => env('RAG_WIDGET_SIGNING_KEY'),
            'ttl_minutes' => (int) env('RAG_WIDGET_SIGNING_TTL_MINUTES', 43200),
        ],
    ],
    'commerce' => [
        'enabled' => (bool) env('RAG_COMMERCIAL_MODE', false),
        'anystack_id' => env('RAG_ANYSTACK_ID'),
        'docs_url' => env('RAG_DOCS_URL'),
        'support_email' => env('RAG_SUPPORT_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workflow / Agentic Actions
    |--------------------------------------------------------------------------
    |
    | Register custom actions that workflow "Action" nodes can dispatch to.
    | Each key is the action identifier used in the workflow editor, and the
    | value is either a callable / invokable class-string, or an array with:
    | - handler: callable or invokable class-string
    | - capability: none | query | write
    |
    | Example:
    |   'actions' => [
    |       'send-email' => [
    |           'handler' => \App\Actions\SendEmailAction::class,
    |           'capability' => 'write',
    |       ],
    |       'lookup-products' => [
    |           'handler' => \App\Actions\LookupProductsAction::class,
    |           'capability' => 'query',
    |       ],
    |   ],
    |
    */
    'workflow' => [
        'actions' => [
            'call-mcp-tool' => [
                'handler'    => \App\Actions\Workflow\CallMcpToolAction::class,
                'capability' => 'query',
            ],
            'list-mcp-tools' => [
                'handler'    => \App\Actions\Workflow\ListMcpToolsAction::class,
                'capability' => 'query',
            ],
            'search-nova-knowledge' => [
                'handler'    => \App\Actions\Workflow\SearchNovaKnowledgeAction::class,
                'capability' => 'query',
            ],
            'normalize-mcp-response' => [
                'handler'    => \App\Actions\Workflow\NormalizeMcpResponseAction::class,
                'capability' => 'query',
            ],
            'register-nova-data' => [
                'handler'    => \App\Actions\Workflow\RegisterNovaDataAction::class,
                'capability' => 'write',
            ],
            'detect-nova-intent' => [
                'handler'    => \App\Actions\Workflow\DetectNovaIntentAction::class,
                'capability' => 'query',
            ],
            'apply-nova-prompt' => [
                'handler'    => \App\Actions\Workflow\ApplyNovaPromptAction::class,
                'capability' => 'query',
            ],
            'apply-nova-cross-selling' => [
                'handler'    => \App\Actions\Workflow\ApplyNovaCrossSellingAction::class,
                'capability' => 'query',
            ],
            'map-service-id' => [
                'handler'    => \App\Actions\Workflow\MapServiceIdAction::class,
                'capability' => 'query',
            ],
            'check-availability' => [
                'handler'    => \App\Actions\Workflow\CheckAvailabilityAction::class,
                'capability' => 'query',
            ],
            'list-tour-services' => [
                'handler'    => \App\Actions\Workflow\ListTourServicesAction::class,
                'capability' => 'query',
            ],
            'create-booking-request' => [
                'handler'    => \App\Actions\Workflow\CreateBookingRequestAction::class,
                'capability' => 'query',
            ],
            'create-remote-booking' => [
                'handler'    => \App\Actions\Workflow\CreateRemoteBookingAction::class,
                'capability' => 'query',
            ],
        ],
        'intent_classifier' => [
            'heuristic_fallback_enabled' => (bool) env('RAG_WORKFLOW_INTENT_HEURISTIC_FALLBACK_ENABLED', true),
            'generic_labels' => array_values(array_filter(array_map(
                static fn ($label) => trim((string) $label),
                explode(',', (string) env('RAG_WORKFLOW_INTENT_GENERIC_LABELS', 'unknown,guide,general,default,other,fallback,help'))
            ), static fn (string $label): bool => $label !== '')),
            'heuristic_stopwords' => array_values(array_filter(array_map(
                static fn ($token) => trim((string) $token),
                explode(',', (string) env('RAG_WORKFLOW_INTENT_HEURISTIC_STOPWORDS', ''))
            ), static fn (string $token): bool => $token !== '')),
            'heuristic_phrase_separators' => array_values(array_filter(array_map(
                static fn ($separator) => trim((string) $separator),
                explode('|', (string) env('RAG_WORKFLOW_INTENT_HEURISTIC_PHRASE_SEPARATORS', ',|;'))
            ), static fn (string $separator): bool => $separator !== '')),
            'heuristic_min_token_length' => (int) env('RAG_WORKFLOW_INTENT_HEURISTIC_MIN_TOKEN_LENGTH', 3),
            'heuristic_min_score' => (int) env('RAG_WORKFLOW_INTENT_HEURISTIC_MIN_SCORE', 2),
        ],
        'input_interruption' => [
            'enabled' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_ENABLED', true),
            'custom_patterns' => [],
            'question_fallback_enabled' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_QUESTION_FALLBACK_ENABLED', true),
            'structural_detection' => [
                'enabled' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_STRUCTURAL_ENABLED', false),
                'min_words_for_invalid_answer' => (int) env('RAG_WORKFLOW_INPUT_INTERRUPTION_MIN_INVALID_WORDS', 2),
            ],
            'semantic_classifier' => [
                'enabled' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_ENABLED', true),
                'inspect_valid_answers' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_VALID_ANSWERS', true),
                'max_output_tokens' => (int) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_MAX_TOKENS', 120),
                'max_retries' => (int) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_MAX_RETRIES', 1),
                'timeout' => (int) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_TIMEOUT', 20),
                'temperature' => (float) env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_TEMPERATURE', 0.0),
                'respect_node_valid_answer_opt_out' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_RESPECT_NODE_SEMANTIC_OPT_OUT', false),
                'min_resume_confidence_for_invalid_answers' => (float) env('RAG_WORKFLOW_INPUT_INTERRUPTION_MIN_RESUME_CONFIDENCE', 0.65),
                'provider' => env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_PROVIDER'),
                'model' => env('RAG_WORKFLOW_INPUT_INTERRUPTION_SEMANTIC_MODEL'),
                'ollama' => [
                    'native_structured_outputs' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_OLLAMA_NATIVE_STRUCTURED_OUTPUTS', true),
                    'think' => env('RAG_WORKFLOW_INPUT_INTERRUPTION_OLLAMA_THINK', false),
                ],
                'structured_outputs' => [
                    'enabled' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_STRUCTURED_OUTPUTS_ENABLED', true),
                    'fallback_to_prompt_json' => (bool) env('RAG_WORKFLOW_INPUT_INTERRUPTION_STRUCTURED_OUTPUTS_FALLBACK', true),
                ],
            ],
        ],
        'choice_resolution' => [
            'semantic_classifier' => [
                'enabled' => (bool) env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_ENABLED', true),
                'max_output_tokens' => (int) env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_MAX_TOKENS', 120),
                'max_retries' => (int) env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_MAX_RETRIES', 1),
                'timeout' => (int) env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_TIMEOUT', 20),
                'temperature' => (float) env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_TEMPERATURE', 0.0),
                'provider' => env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_PROVIDER'),
                'model' => env('RAG_WORKFLOW_CHOICE_RESOLUTION_SEMANTIC_MODEL'),
                'ollama' => [
                    'native_structured_outputs' => (bool) env('RAG_WORKFLOW_CHOICE_RESOLUTION_OLLAMA_NATIVE_STRUCTURED_OUTPUTS', true),
                    'think' => env('RAG_WORKFLOW_CHOICE_RESOLUTION_OLLAMA_THINK', false),
                ],
                'structured_outputs' => [
                    'enabled' => (bool) env('RAG_WORKFLOW_CHOICE_RESOLUTION_STRUCTURED_OUTPUTS_ENABLED', true),
                    'fallback_to_prompt_json' => (bool) env('RAG_WORKFLOW_CHOICE_RESOLUTION_STRUCTURED_OUTPUTS_FALLBACK', true),
                ],
            ],
        ],
        'turn_router' => [
            'enabled' => (bool) env('RAG_WORKFLOW_TURN_ROUTER_ENABLED', true),
            'cancellation_enabled' => (bool) env('RAG_WORKFLOW_TURN_ROUTER_CANCELLATION_ENABLED', true),
            'cancelled_message' => env('RAG_WORKFLOW_TURN_ROUTER_CANCELLED_MESSAGE', 'Workflow cancelled.'),
            'max_clarification_turns' => (int) env('RAG_WORKFLOW_TURN_ROUTER_MAX_CLARIFICATION_TURNS', 1),
            'cancel_patterns' => array_values(array_filter(array_map(
                static fn ($pattern) => trim((string) $pattern),
                preg_split('/\R/u', (string) env('RAG_WORKFLOW_TURN_ROUTER_CANCEL_PATTERNS', '')) ?: []
            ), static fn (string $pattern): bool => $pattern !== '')),
        ],
        'store_submission' => [
            'require_confirmation' => (bool) env('RAG_WORKFLOW_STORE_SUBMISSION_REQUIRE_CONFIRMATION', true),
        ],
        'image_generation' => [
            'transport' => env('RAG_WORKFLOW_IMAGE_TRANSPORT', 'auto'),
            'provider' => env('RAG_WORKFLOW_IMAGE_PROVIDER'),
            'model' => env('RAG_WORKFLOW_IMAGE_MODEL'),
            'size' => env('RAG_WORKFLOW_IMAGE_SIZE', '1:1'),
            'quality' => env('RAG_WORKFLOW_IMAGE_QUALITY', 'medium'),
            'width' => env('RAG_WORKFLOW_IMAGE_WIDTH') !== null ? (int) env('RAG_WORKFLOW_IMAGE_WIDTH') : null,
            'height' => env('RAG_WORKFLOW_IMAGE_HEIGHT') !== null ? (int) env('RAG_WORKFLOW_IMAGE_HEIGHT') : null,
            'steps' => env('RAG_WORKFLOW_IMAGE_STEPS') !== null ? (int) env('RAG_WORKFLOW_IMAGE_STEPS') : null,
            'timeout' => (int) env('RAG_WORKFLOW_IMAGE_TIMEOUT', 300),
            'disk' => env('RAG_WORKFLOW_IMAGE_DISK', 'public'),
            'path' => env('RAG_WORKFLOW_IMAGE_PATH', 'workflow-images'),
            'public_base_url' => env('RAG_WORKFLOW_IMAGE_PUBLIC_BASE_URL'),
            'http' => [
                'provider' => env('RAG_WORKFLOW_IMAGE_HTTP_PROVIDER', 'custom_http'),
                'url' => env('RAG_WORKFLOW_IMAGE_HTTP_URL'),
                'headers' => json_decode((string) env('RAG_WORKFLOW_IMAGE_HTTP_HEADERS', '[]'), true) ?: [],
                'allowed_hosts' => array_values(array_filter(array_map(
                    static fn ($host) => trim((string) $host),
                    explode(',', (string) env('RAG_WORKFLOW_IMAGE_HTTP_ALLOWED_HOSTS', '127.0.0.1,localhost,host.docker.internal,::1'))
                ), static fn (string $host): bool => $host !== '')),
            ],
        ],
        /*
        |--------------------------------------------------------------------------
        | Action Mapping Schemas
        |--------------------------------------------------------------------------
        |
        | Optional schema metadata for workflow action inputMapping payloads.
        | Keys must match the actionKey. Schemas use a lightweight JSON-schema-
        | style shape with type, properties, required, default, enum, items,
        | and additionalProperties.
        |
        | Example:
        | 'action_schemas' => [
        |     'send-email' => [
        |         'type' => 'object',
        |         'required' => ['to', 'subject', 'body'],
        |         'additionalProperties' => false,
        |         'properties' => [
        |             'to' => ['type' => 'string'],
        |             'subject' => ['type' => 'string'],
        |             'body' => ['type' => 'string'],
        |             'priority' => ['type' => 'string', 'enum' => ['low', 'normal', 'high'], 'default' => 'normal'],
        |         ],
        |     ],
        | ],
        |
        */
        'action_schemas' => [
            'store_submission' => [
                'type' => 'object',
                'required' => ['schema_key', 'payload'],
                'additionalProperties' => false,
                'properties' => [
                    'schema_key' => ['type' => 'string'],
                    'schema_version' => ['type' => 'integer'],
                    'status' => ['type' => 'string'],
                    'dedupe_key' => ['type' => 'string', 'nullable' => true],
                    'payload' => [
                        'type' => 'object',
                    ],
                    'meta' => [
                        'type' => 'object',
                        'nullable' => true,
                    ],
                ],
            ],
            'query_data_resource' => [
                'type' => 'object',
                'required' => ['resource_key'],
                'additionalProperties' => false,
                'properties' => [
                    'resource_key' => ['type' => 'string'],
                    'mode' => ['type' => 'string', 'enum' => ['list', 'first'], 'default' => 'list'],
                    'filters' => [
                        'type' => 'object',
                        'nullable' => true,
                    ],
                    'filter_clauses' => [
                        'type' => 'array',
                        'nullable' => true,
                        'items' => [
                            'type' => 'object',
                            'required' => ['field'],
                            'additionalProperties' => false,
                            'properties' => [
                                'field' => ['type' => 'string'],
                                'operator' => [
                                    'type' => 'string',
                                    'enum' => [
                                        'equals',
                                        'not_equals',
                                        'contains',
                                        'starts_with',
                                        'ends_with',
                                        'in',
                                        'not_in',
                                        'gt',
                                        'gte',
                                        'lt',
                                        'lte',
                                        'between',
                                        'is_null',
                                        'not_null',
                                    ],
                                    'default' => 'equals',
                                ],
                                'value' => ['type' => 'any', 'nullable' => true],
                                'values' => [
                                    'type' => 'array',
                                    'nullable' => true,
                                    'items' => ['type' => 'any'],
                                ],
                            ],
                        ],
                    ],
                    'select' => [
                        'type' => 'array',
                        'nullable' => true,
                        'items' => ['type' => 'string'],
                    ],
                    'limit' => ['type' => 'integer'],
                    'sort' => [
                        'type' => 'object',
                        'nullable' => true,
                        'additionalProperties' => false,
                        'properties' => [
                            'column' => ['type' => 'string'],
                            'direction' => ['type' => 'string', 'enum' => ['asc', 'desc'], 'default' => 'asc'],
                        ],
                    ],
                ],
            ],
            'format_records_for_chat' => [
                'type' => 'object',
                'required' => ['source'],
                'additionalProperties' => false,
                'properties' => [
                    'source' => ['type' => 'object'],
                    'presentation' => ['type' => 'string', 'enum' => ['cards', 'image_gallery', 'bullet_list'], 'default' => 'cards'],
                    'title_field' => ['type' => 'string', 'default' => 'name'],
                    'body_fields' => [
                        'type' => 'array',
                        'items' => ['type' => 'string'],
                    ],
                    'image_field' => ['type' => 'string'],
                    'url_field' => ['type' => 'string'],
                    'empty_message' => ['type' => 'string'],
                    'intro' => ['type' => 'string'],
                    'max_items' => ['type' => 'integer', 'default' => 6],
                ],
            ],
            'generate_image' => [
                'type' => 'object',
                'required' => ['prompt'],
                'additionalProperties' => false,
                'properties' => [
                    'prompt' => ['type' => 'string'],
                    'transport' => ['type' => 'string', 'enum' => ['auto', 'laravel_ai', 'http_json'], 'default' => env('RAG_WORKFLOW_IMAGE_TRANSPORT', 'auto')],
                    'provider' => ['type' => 'string', 'nullable' => true],
                    'model' => ['type' => 'string', 'nullable' => true],
                    'url' => ['type' => 'string', 'nullable' => true],
                    'headers' => ['type' => 'object', 'nullable' => true],
                    'body' => ['type' => 'object', 'nullable' => true],
                    'merge_body' => ['type' => 'boolean', 'default' => true],
                    'response_image_path' => ['type' => 'string', 'nullable' => true],
                    'response_image_url_path' => ['type' => 'string', 'nullable' => true],
                    'response_mime_path' => ['type' => 'string', 'nullable' => true],
                    'size' => ['type' => 'string', 'enum' => ['1:1', '2:3', '3:2'], 'default' => env('RAG_WORKFLOW_IMAGE_SIZE', '1:1')],
                    'quality' => ['type' => 'string', 'enum' => ['low', 'medium', 'high'], 'default' => env('RAG_WORKFLOW_IMAGE_QUALITY', 'medium')],
                    'width' => ['type' => 'integer'],
                    'height' => ['type' => 'integer'],
                    'steps' => ['type' => 'integer'],
                    'timeout' => ['type' => 'integer', 'default' => (int) env('RAG_WORKFLOW_IMAGE_TIMEOUT', 300)],
                    'disk' => ['type' => 'string', 'default' => env('RAG_WORKFLOW_IMAGE_DISK', 'public')],
                    'path' => ['type' => 'string', 'default' => env('RAG_WORKFLOW_IMAGE_PATH', 'workflow-images')],
                    'public_base_url' => ['type' => 'string', 'nullable' => true],
                ],
            ],
        ],
        /*
        |--------------------------------------------------------------------------
        | API Connector Mapping Schemas
        |--------------------------------------------------------------------------
        |
        | Optional schema metadata for apiConnector nodes. Keys may match a
        | connector's ID, name, or base URL. Each schema can define headers,
        | body, and a default response JSON path.
        |
        | Example:
        | 'connector_schemas' => [
        |     'Customer API' => [
        |         'headers' => [
        |             'type' => 'object',
        |             'properties' => [
        |                 'X-Request-Id' => ['type' => 'string'],
        |             ],
        |         ],
        |         'body' => [
        |             'type' => 'object',
        |             'required' => ['customer_id'],
        |             'properties' => [
        |                 'customer_id' => ['type' => 'integer'],
        |             ],
        |         ],
        |         'response' => [
        |             'default_json_path' => 'data.customer',
        |         ],
        |     ],
        | ],
        |
        */
        'connector_schemas' => [],
        'max_steps' => (int) env('RAG_WORKFLOW_MAX_STEPS', 50),
        'concurrency' => [
            'running_timeout_seconds' => (int) env('RAG_WORKFLOW_RUNNING_TIMEOUT_SECONDS', 120),
        ],
        'traces' => [
            'capture_input' => (bool) env('RAG_WORKFLOW_TRACE_CAPTURE_INPUT', true),
            'capture_output' => (bool) env('RAG_WORKFLOW_TRACE_CAPTURE_OUTPUT', true),
            'capture_variables' => (bool) env('RAG_WORKFLOW_TRACE_CAPTURE_VARIABLES', true),
            'capture_meta' => (bool) env('RAG_WORKFLOW_TRACE_CAPTURE_META', true),
            'max_string_length' => (int) env('RAG_WORKFLOW_TRACE_MAX_STRING_LENGTH', 2000),
            'redacted_keys' => array_values(array_filter(array_map(
                static fn ($key) => strtolower(trim((string) $key)),
                explode(',', (string) env('RAG_WORKFLOW_TRACE_REDACT_KEYS', 'email,password,passphrase,secret,token,access_token,refresh_token,authorization,api_key,apikey,private_key,phone,ssn'))
            ))),
            'redacted_value_patterns' => (static function (): array {
                $configured = json_decode((string) env('RAG_WORKFLOW_TRACE_REDACT_VALUE_PATTERNS', '[]'), true);
                $patterns = is_array($configured) && $configured !== []
                    ? $configured
                    : [
                        '~[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}~i',
                    ];

                return array_values(array_filter(array_map(
                    static fn ($pattern): string => trim((string) $pattern),
                    $patterns,
                ), static fn (string $pattern): bool => $pattern !== ''));
            })(),
            'redacted_value' => env('RAG_WORKFLOW_TRACE_REDACT_VALUE', '[REDACTED]'),
        ],
        'generation' => [
            'provider' => env('RAG_WORKFLOW_GENERATION_PROVIDER'),
            'model' => env('RAG_WORKFLOW_GENERATION_MODEL'),
            'connection' => env('RAG_WORKFLOW_GENERATION_QUEUE_CONNECTION', ''),
            'queue' => env('RAG_WORKFLOW_GENERATION_QUEUE', ''),
            'max_attempts' => (int) env('RAG_WORKFLOW_GENERATION_MAX_ATTEMPTS', 3),
            'job_timeout' => (int) env('RAG_WORKFLOW_GENERATION_JOB_TIMEOUT', 180),
            'poll_interval_ms' => (int) env('RAG_WORKFLOW_GENERATION_POLL_INTERVAL_MS', 2000),
            'max_prompt_length' => (int) env('RAG_WORKFLOW_GENERATION_MAX_PROMPT_LENGTH', 5000),
        ],
        'streaming' => [
            'llm_default' => (bool) env('RAG_WORKFLOW_STREAMING_LLM_DEFAULT', true),
            'simulate_deterministic' => (bool) env('RAG_WORKFLOW_STREAMING_SIMULATE_DETERMINISTIC', true),
            'deterministic_delay_ms' => (int) env('RAG_WORKFLOW_STREAMING_DETERMINISTIC_DELAY_MS', 15),
            'deterministic_chunk_size' => (int) env('RAG_WORKFLOW_STREAMING_DETERMINISTIC_CHUNK_SIZE', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Submission Capture
    |--------------------------------------------------------------------------
    |
    | Configure safe, schema-driven records that workflow actions can persist
    | through the built-in `store_submission` action. Each schema key may also
    | be overridden per bot via `rag_config.submissions.schemas`.
    |
    | Example:
    | 'schemas' => [
    |     'lead_capture' => [
    |         'label' => 'Lead Capture',
    |         'schema_version' => 1,
    |         'default_status' => 'submitted',
    |         'allowed_statuses' => ['submitted', 'confirmed'],
    |         'dedupe_key_path' => 'email',
    |         'require_consent' => true,
    |         'consent_variable' => 'lead_consent',
    |         'payload_schema' => [
    |             'type' => 'object',
    |             'required' => ['email'],
    |             'additionalProperties' => false,
    |             'properties' => [
    |                 'email' => ['type' => 'string'],
    |                 'name' => ['type' => 'string'],
    |             ],
    |         ],
    |     ],
    | ],
    |
    */
    'submissions' => [
        'require_registered_schema' => true,
        'schemas' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal Data Resources
    |--------------------------------------------------------------------------
    |
    | Read-only resource definitions that workflows can query through the
    | built-in `query_data_resource` action. Define resources globally here
    | and explicitly allow them per bot via `rag_config.data_resources`.
    |
    */
    'data_resources' => [
        'require_explicit_bot_allow_list' => true,
        'smart_queries' => [
            'enabled' => true,
            'default_limit' => 5,
            'max_limit' => 10,
        ],
        'resources' => [
            'bots' => [
                'label' => 'Bots',
                'description' => 'Read-only bot catalog for workflow inventory and source explanations.',
                'query_guidance' => 'Use this to explain which bots exist or to resolve public identifiers. Keep select minimal when exposing bot details in chat.',
                'model' => RagBot::class,
                'allowed_modes' => ['list', 'first'],
                'allowed_filters' => ['name', 'is_active', 'public_id'],
                'allowed_selects' => ['id', 'name', 'public_id', 'is_active', 'created_at'],
                'sortable_fields' => ['id', 'name', 'created_at'],
                'default_select' => ['id', 'name', 'public_id', 'is_active'],
                'default_sort' => ['column' => 'name', 'direction' => 'asc'],
                'default_limit' => 10,
                'max_limit' => 25,
                'field_metadata' => [
                    'name' => [
                        'type' => 'string',
                        'label' => 'Bot name',
                        'description' => 'Human-readable bot name for name/title lookups.',
                        'aliases' => ['name', 'title', 'label'],
                    ],
                    'created_at' => [
                        'type' => 'datetime',
                        'label' => 'Created date',
                        'description' => 'Creation timestamp; sort desc for newest/latest and asc for oldest.',
                        'aliases' => ['created', 'newest', 'latest', 'oldest'],
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'label' => 'Active state',
                        'description' => 'Whether the bot is active.',
                        'aliases' => ['active', 'enabled'],
                    ],
                ],
            ],
            'agent_workflows' => [
                'label' => 'Workflows',
                'description' => 'Read-only workflow catalog that exposes names, descriptions, and activation state.',
                'query_guidance' => 'Prefer this for workflow inventory and status explanations. Runtime always scopes these records to the linked bot.',
                'model' => AgentWorkflow::class,
                'allowed_modes' => ['list', 'first'],
                'allowed_filters' => ['name', 'is_active', 'rag_bot_id'],
                'scope_filters' => ['rag_bot_id' => 'bot.id'],
                'allowed_selects' => ['id', 'name', 'description', 'is_active', 'published_at', 'rag_bot_id', 'created_at'],
                'sortable_fields' => ['id', 'name', 'published_at', 'created_at'],
                'default_select' => ['id', 'name', 'description', 'is_active', 'published_at', 'rag_bot_id'],
                'default_sort' => ['column' => 'name', 'direction' => 'asc'],
                'default_limit' => 25,
                'max_limit' => 100,
                'field_metadata' => [
                    'name' => [
                        'type' => 'string',
                        'label' => 'Workflow name',
                        'description' => 'Human-readable workflow name.',
                        'aliases' => ['name', 'title', 'workflow'],
                    ],
                    'published_at' => [
                        'type' => 'datetime',
                        'label' => 'Published date',
                        'description' => 'Publication timestamp; sort desc for newest/latest published workflow.',
                        'aliases' => ['published', 'newest', 'latest', 'oldest'],
                    ],
                    'created_at' => [
                        'type' => 'datetime',
                        'label' => 'Created date',
                        'description' => 'Creation timestamp; sort desc for newest/latest created workflow.',
                        'aliases' => ['created', 'newest', 'latest', 'oldest'],
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'label' => 'Active state',
                        'description' => 'Whether the workflow is active.',
                        'aliases' => ['active', 'enabled'],
                    ],
                ],
            ],
            'rag_sources' => [
                'label' => 'RAG Sources',
                'description' => 'Read-only source catalog for explaining ingestion coverage and source status.',
                'query_guidance' => 'Use this to summarize source coverage or ingestion status. Runtime always scopes these records to the linked bot.',
                'model' => RagSource::class,
                'allowed_modes' => ['list', 'first'],
                'allowed_filters' => ['name', 'type', 'status', 'rag_bot_id'],
                'scope_filters' => ['rag_bot_id' => 'bot.id'],
                'allowed_selects' => ['id', 'name', 'type', 'status', 'rag_bot_id', 'created_at', 'updated_at'],
                'sortable_fields' => ['id', 'name', 'type', 'status', 'created_at', 'updated_at'],
                'default_select' => ['id', 'name', 'type', 'status', 'rag_bot_id', 'updated_at'],
                'default_sort' => ['column' => 'name', 'direction' => 'asc'],
                'default_limit' => 25,
                'max_limit' => 100,
                'field_metadata' => [
                    'name' => [
                        'type' => 'string',
                        'label' => 'Source name',
                        'description' => 'Human-readable source name.',
                        'aliases' => ['name', 'title', 'source'],
                    ],
                    'status' => [
                        'type' => 'enum',
                        'label' => 'Ingestion status',
                        'description' => 'Current source ingestion status.',
                        'aliases' => ['status', 'state'],
                    ],
                    'created_at' => [
                        'type' => 'datetime',
                        'label' => 'Created date',
                        'description' => 'Creation timestamp; sort desc for newest/latest source.',
                        'aliases' => ['created', 'newest', 'latest', 'oldest'],
                    ],
                    'updated_at' => [
                        'type' => 'datetime',
                        'label' => 'Updated date',
                        'description' => 'Last update timestamp; sort desc for recently updated source.',
                        'aliases' => ['updated', 'recent', 'latest', 'oldest'],
                    ],
                ],
            ],
            'rag_submissions' => [
                'label' => 'Submissions',
                'description' => 'Read-only submission index for explaining captured dogfood items without exposing payload PII.',
                'query_guidance' => 'Use this for status or volume checks only. Payload data stays hidden and runtime always scopes records to the linked bot.',
                'model' => RagSubmission::class,
                'allowed_modes' => ['list', 'first'],
                'allowed_filters' => ['schema_key', 'status', 'rag_bot_id', 'agent_workflow_id'],
                'scope_filters' => ['rag_bot_id' => 'bot.id'],
                'allowed_selects' => ['id', 'schema_key', 'status', 'rag_bot_id', 'agent_workflow_id', 'submitted_at', 'created_at'],
                'sortable_fields' => ['id', 'schema_key', 'status', 'submitted_at', 'created_at'],
                'default_select' => ['id', 'schema_key', 'status', 'agent_workflow_id', 'submitted_at'],
                'default_sort' => ['column' => 'submitted_at', 'direction' => 'desc'],
                'default_limit' => 25,
                'max_limit' => 100,
                'field_metadata' => [
                    'schema_key' => [
                        'type' => 'string',
                        'label' => 'Submission schema',
                        'description' => 'Registered submission schema key.',
                        'aliases' => ['schema', 'type', 'kind'],
                    ],
                    'status' => [
                        'type' => 'enum',
                        'label' => 'Submission status',
                        'description' => 'Current submission status.',
                        'aliases' => ['status', 'state'],
                    ],
                    'submitted_at' => [
                        'type' => 'datetime',
                        'label' => 'Submitted date',
                        'description' => 'Submission timestamp; sort desc for newest/latest submission and asc for oldest.',
                        'aliases' => ['submitted', 'newest', 'latest', 'oldest'],
                    ],
                    'created_at' => [
                        'type' => 'datetime',
                        'label' => 'Created date',
                        'description' => 'Creation timestamp; sort desc for newest/latest record.',
                        'aliases' => ['created', 'newest', 'latest', 'oldest'],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bot Capabilities
    |--------------------------------------------------------------------------
    |
    | Controls whether a bot may query the knowledge base, write/store data,
    | or do both. Bots can override this per-record via:
    | `rag_config.capabilities.mode`.
    |
    | Supported modes:
    | - query_only
    | - write_only
    | - query_and_write
    |
    */
    'capabilities' => [
        'default_mode' => 'query_only',
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Tool Calling
    |--------------------------------------------------------------------------
    |
    | Configure the tools available to the LLM agent during conversations.
    | Tools allow the model to call functions (knowledge search, date/time,
    | custom actions) and receive structured results before composing its
    | final answer.
    |
    | 'enabled' — Master switch for tool calling. When false, no tools are
    |             passed to the LLM, even if registered.
    |
    | 'built_in' — Toggle individual built-in tools. Each key corresponds
    |              to a tool that ships with the plugin.
    |
    | 'custom' — Register your own Tool implementations. Each key is the
    |            tool name (used in per-bot config), value is an invokable
    |            class-string implementing Laravel\Ai\Contracts\Tool.
    |
    | Per-bot tool filtering is done via the bot's rag_config JSON:
    |   { "tools": { "enabled": ["knowledge_search", "current_datetime"] } }
    |   { "tools": { "enabled": false } }  // disables all tools for this bot
    |
    */
    'tools' => [
        'enabled' => (bool) env('RAG_TOOLS_ENABLED', true),
        'built_in' => [
            'current_datetime' => false, // Temporarily disabled due to schema validation error
        ],
        'custom' => [
            // 'my_tool' => \App\Ai\Tools\MyCustomTool::class,
        ],
    ],

    'vector_dimensions' => (static function (): ?int {
        $configured = env('RAG_VECTOR_DIMENSIONS');

        if ($configured === null || trim((string) $configured) === '') {
            return null;
        }

        return max(1, (int) $configured);
    })(),
];
