# Nova MCP HUB — Diagrama de Infraestructura Completo

> Generado: 2026-06-30 | Stack: Laravel 12 · PHP 8.4 · Filament v5 · Livewire v4 · laravel/mcp v0.7 · laravel/ai v0.6

---

## 1. Visión General del Sistema

```mermaid
graph TB
    subgraph ENTRADA["🌐 CANALES DE ENTRADA"]
        WA["📱 WhatsApp Business Cloud API\nwebhook: /api/nova/whatsapp/webhook"]
        EXPLORE["🗺️ /explore\nMarketplace público (Livewire)"]
        ADMIN["⚙️ /admin\nFilament Panel (SPA)"]
        AIBOT["🤖 /ai-bot\nChat público IA"]
        MCPEXT["🔌 Clientes MCP externos\nCursor / Claude / VS Code"]
        APICLI["📡 API REST\n/api/* · /mcp/*"]
    end

    subgraph CORE["🏗️ NÚCLEO LARAVEL 12"]
        BOOT["bootstrap/app.php\nroutes · middleware · commands"]
        QUEUE["Queue Worker\ndriver: database\njobs · failed_jobs · job_batches"]
        REVERB["Laravel Reverb :8080\nWebSocket Broadcasting"]
        SCHED["Scheduler\nroutes/console.php"]
    end

    subgraph PANELS["🖥️ FILAMENT v5 PANELS"]
        ADMINPANEL["AdminPanelProvider\n/admin (SPA, color: Red)\n20+ resource directories"]
        KBPANEL["KnowledgeBasePanelProvider\n/knowledge-base (color: Amber)"]
    end

    WA --> CORE
    EXPLORE --> CORE
    ADMIN --> PANELS
    AIBOT --> CORE
    MCPEXT --> CORE
    APICLI --> CORE
    PANELS --> CORE
    CORE --> QUEUE
    CORE --> REVERB
    CORE --> SCHED
```


---

## 2. Capa MCP — Hub de Servidores

```mermaid
graph LR
    subgraph NOVA_MCP_SERVER["🟢 NOVA como SERVIDOR MCP"]
        MCPGen["McpServerGenerator\nlee tabla servers DB\nregistra handlers dinámicos"]
        DynServer["DynamicServer\nDynamicTool\nDynamicResource\nDynamicPrompt"]
        MCPEndpoint["Endpoints:\n/mcp/nova/*\n/api/mcp/*"]
        MCPGen --> DynServer --> MCPEndpoint
    end

    subgraph NOVA_MCP_CLIENT["🔵 NOVA como CLIENTE MCP"]
        direction TB
        LB["laravel-boost\nphp artisan boost:mcp\n(docs dev)"]
        FIL["filamentphp\nnpx filamentphp-mcp\n(docs Filament)"]
        TW["tinkerwell\nbinario local ARM64\n(PHP REPL)"]
        TAXIWP["taxilanz-wordpress\nmcp-remote → taxilanzwp7.test\n(WP con Basic Auth)"]
        SIRVO["sirvo\nhttp://192.168.1.50:3000\n(restaurante LAN)"]
        MAG["magento2\nNode.js mcp-server.js\n(Lanzaloe Magento)"]
        WPSTUDIO["wordpress-studio\nstudio mcp"]
        HERD["herd\nherd-mcp.phar\n(Laravel Herd)"]
        LAGERIA["www-lageria-com\nhttps://lageria.com/wp-json/mcp/v1/http\n(Bearer token)"]
        CTX7["context7\nnpx @upstash/context7-mcp\n(docs contexto)"]
        TBPLUS["tableplus\nTablePlus.app MCP\n(DB GUI)"]
        ERASER["eraser\nhttps://app.eraser.io/api/mcp\n(diagramas)"]
        MIRO["miro-mcp\nhttps://mcp.miro.com/\n(boards)"]
        FLOWBITE["flowbite\nnpx flowbite-mcp\n(UI docs)"]
        FILEXAMPLES["filament-examples\nhttps://filamentexamples.com/mcp\n(ejemplos)"]
    end

    MCPEndpoint --> NOVA_MCP_CLIENT
    TAXIHOTELES["taxilanz-hoteles\nhttps://novahubmcp.test/api/mcp/\n(self-ref)"] --> MCPEndpoint
```


---

## 3. Capa de Inteligencia Artificial

```mermaid
graph TB
    subgraph AI_LAYER["🧠 CAPA IA — laravel/ai v0.6"]
        subgraph AGENTS["Agentes (app/Ai/Agents/)"]
            NH["NovaHub\n(orquestador principal)"]
            NIA["NovaIntentAgent\n(clasifica intención)"]
            NBEA["NovaBookingExtractionAgent\n(extrae datos reserva)"]
            NRA["NovaResponseAgent\n(genera respuesta final)"]
            NH --> NIA
            NH --> NBEA
            NH --> NRA
        end

        subgraph AI_SERVICES["Servicios IA (app/Services/Nova/)"]
            NAIORQ["NovaOrchestratorService"]
            NAISERV["NovaAiService"]
            NCONV["NovaConversationContextService"]
            NEXTRACT["NovaConversationDataExtractor"]
            NKNOW["NovaKnowledgeService\n+ NovaKnowledgeEmbedder"]
            NXSELL["NovaCrossSellingService"]
            NWEB["NovaWebsiteKnowledgeImporter"]
        end

        subgraph LLM_PROVIDERS["Proveedores LLM"]
            OPENAI["OpenAI\ngpt-3.5-turbo (chat)\ntext-embedding-ada-002 (RAG)"]
            ANTHROPIC["Anthropic Claude"]
            OLLAMA["Ollama local LAN\nqwen3:4b\n192.168.1.130:11434"]
        end

        subgraph RAG["RAG / Embeddings"]
            PGVEC["pgvector PostgreSQL\nfilament_agentic_chatbot DB\n1536 dimensiones"]
            NOVAKNOW["NovaAiKnowledge\n(modelo + Observer auto-embed)"]
            NOVAKNOW --> PGVEC
        end

        AGENTS --> AI_SERVICES
        AI_SERVICES --> LLM_PROVIDERS
        AI_SERVICES --> RAG
    end
```


---

## 4. Modelo de Datos — NovaBusiness como raíz

```mermaid
erDiagram
    NovaBusiness ||--o{ NovaService : "has"
    NovaBusiness ||--o{ Server : "owns (MCP)"
    NovaBusiness ||--o{ NovaAiProfile : "has"
    NovaBusiness ||--o{ NovaAiKnowledge : "has"
    NovaBusiness ||--o{ NovaWhatsappChannel : "has"
    NovaBusiness ||--o{ NovaIntentRule : "has"
    NovaBusiness ||--o{ NovaCrossSellingRule : "has"
    NovaBusiness ||--o{ NovaListingCategory : "has"
    NovaBusiness ||--o{ NovaIntegrationSetting : "has"
    NovaBusiness ||--o{ NovaIntegrationSyncLog : "has"
    NovaBusiness ||--o{ NovaExternalBooking : "has"
    NovaBusiness ||--o{ NovaExternalOrder : "has"
    NovaBusiness ||--o{ NovaExternalTransaction : "has"
    NovaBusiness ||--o{ NovaExternalCatalogItem : "has"
    NovaBusiness ||--o{ NovaExternalCustomer : "has"
    NovaBusiness ||--o{ NovaRequest : "has"
    NovaBusiness ||--o{ NovaModule : "has"

    Server ||--o{ Tool : "has"
    Server ||--o{ Resource : "has"
    Server ||--o{ Prompt : "has"
    Server ||--o{ McpLog : "logs"

    NovaWhatsappChannel ||--o{ NovaWhatsappMessage : "has"
    NovaIntentToServerMapping }o--|| Server : "maps to"

    User ||--o{ TaxiBooking : "creates"
    User ||--o{ TourBooking : "creates"
    User ||--o{ HotelBooking : "creates"
    User ||--o{ RestaurantBooking : "creates"
    User ||--o{ RentalBooking : "creates"

    TaxiBooking ||--o{ Payment : "has"
    TaxiBooking ||--|{ Driver : "assigned"
    TaxiBooking ||--|{ Vehicle : "uses"

    Factura ||--o{ RegistroFactura : "has"
    Cliente ||--o{ Factura : "has"
    Empresa ||--o{ Factura : "has"

    Workflow ||--o{ WorkflowAuditLog : "logs"
    AgentConversation ||--o{ NovaRequest : "linked"
```


---

## 5. Flujo Completo — WhatsApp → IA → Reserva → Pago

```mermaid
sequenceDiagram
    actor Usuario
    participant WA as WhatsApp Business API
    participant WH as POST /api/nova/whatsapp/webhook
    participant NWH as NovaWhatsappWebhookController
    participant NOS as NovaOrchestratorService
    participant NIA as NovaIntentAgent (laravel/ai)
    participant NBEA as NovaBookingExtractionAgent
    participant MCP as MCP Tools (dinámicos)
    participant DB as MySQL mcp_studio2026
    participant REV as Reverb WebSocket
    participant ADMIN as Filament /admin

    Usuario->>WA: "Quiero reservar taxi para mañana"
    WA->>WH: POST webhook (JSON)
    WH->>NWH: handle()
    NWH->>NOS: orchestrate(message, channel)
    NOS->>NIA: classify intent
    NIA-->>NOS: intent: "taxi_booking"
    NOS->>NBEA: extract booking data
    NBEA-->>NOS: {origin, destination, datetime}
    NOS->>MCP: call tool "check_availability"
    MCP-->>NOS: slots disponibles
    NOS->>DB: create NovaRequest + NovaExternalBooking
    NOS->>WA: respuesta con opciones
    WA-->>Usuario: "He encontrado disponibilidad..."
    Usuario->>WA: "Confirmar opción 1"
    WA->>WH: POST confirmación
    NWH->>NOS: orchestrate(confirm)
    NOS->>DB: create TaxiBooking / PublicBookingRequest
    NOS->>REV: broadcast TaxiBookingCreated
    REV->>ADMIN: actualiza dashboard en tiempo real
    NOS->>WA: "Reserva confirmada ✅"
    WA-->>Usuario: confirmación final
```


---

## 6. Integraciones Externas — Sincronización

```mermaid
graph LR
    subgraph NOVA["Nova MCP HUB"]
        ESS["ExternalSync Services"]
        JOBS["Artisan Commands\nnova:sync-magento\nnova:sync-woo-latepoint\nnova:register-external-integrations"]
        MODELS["ExternalSource\nExternalSyncMapping\nExternalBooking\nExternalOrder\nExternalCatalogItem\nExternalPayment\nExternalSyncLog"]
        ESS --> JOBS
        ESS --> MODELS
    end

    subgraph EXTERNAL["Sistemas Externos"]
        MAG2["Lanzaloe\nMagento 2\nREST API + Admin Token"]
        WOO["Taxilanz / Lageria\nWooCommerce REST API\nConsumer Key/Secret"]
        LP["LatePoint\nBooking System\nAPI Sync"]
        WP1["taxilanzwp7.test\nWordPress\nMCP-remote (Basic Auth)"]
        WP2["lageria.com\nWordPress\nMCP Bearer token"]
        SIRVO_EXT["Sirvo\nRestaurante LAN\n192.168.1.42:3000\nBearer token"]
    end

    subgraph PAYMENTS_EXT["Pasarelas de Pago"]
        REDSYS["Redsys TPV Virtual\n(ES) - Modo test\nredsys/tpv ^2.3"]
        P24["Przelewy24\n(PL) - Sandbox\nLegacy/secundario"]
    end

    JOBS --> MAG2
    JOBS --> WOO
    JOBS --> LP
    ESS --> WP1
    ESS --> WP2
    ESS --> SIRVO_EXT
    MODELS --> REDSYS
    MODELS --> P24
```


---

## 7. Stack de Servicios — Capas internas

```mermaid
graph TB
    subgraph HTTP["HTTP Layer"]
        CTRL["Controllers\nApi/ · MapController\nRedsysController · VoiceController\nMCPController · PanelBuilderController"]
        LW["Livewire Components\nTourist/ · Providers/\nIntegratedPanelManager\nWorkflowPanelManager\nReactFlowEditor"]
        VOLT["Volt Single-File\nfacturacion/* · test"]
    end

    subgraph SVC["Services Layer (app/Services/)"]
        direction LR
        SVC_AI["AI/\nOrchestration"]
        SVC_NOVA["Nova/\n19 servicios:\nOrchestratorService\nWhatsAppCloudService\nMcpClient\nKnowledgeEmbedder\nMagentoApiSync\nWooCommerceApiSync\nLatePointApiSync\nSirvoReservationClient\n..."]
        SVC_TAXI["TaxiBooking/\nTaxiService/\nTrip/\nDriver/\nVehicle/"]
        SVC_BOOK["Bookings/\nRental/\nCancellation/\nRating/"]
        SVC_PAY["Payments/\nPricing/\nRedsys · Przelewy24"]
        SVC_EXT["ExternalSync/\nMCP/"]
        SVC_GEO["GeoapifyService\nGeocodingService\nGpsTrackingService"]
        SVC_MISC["McpServerGenerator\nToolExecutor\nSchemaBuilder\nCodeGeneratorService\nRecommendationEngine\nSlotService\nSpeechAnalysisService"]
    end

    subgraph DOMAIN["Domain Models (app/Models/)"]
        DOM_NOVA["Nova domain\nNovaBusiness · NovaService\nNovaAiProfile · NovaAiKnowledge\nNovaWhatsappChannel/Message\nNovaIntentRule · NovaCrossSellingRule\nNovaIntentToServerMapping\nNovaRequest · NovaModule"]
        DOM_MCP["MCP domain\nServer · Tool · Resource\nPrompt · McpLog"]
        DOM_TAXI["Taxi domain\nTaxiService · TaxiBooking\nDriver · Vehicle · VehicleType\nTrip · Ride · TransferTariff"]
        DOM_TOUR["Tourism domain\nTour · TourBooking · TourSchedule\nHotel · HotelBooking · RoomType\nRestaurant · RestaurantBooking\nRental · RentalBooking · RentalVehicle"]
        DOM_EXT["External Sync\nExternalSource · ExternalBooking\nExternalOrder · ExternalPayment\nExternalCatalogItem · ExternalSyncLog"]
        DOM_BILL["Facturación\nFactura · RegistroFactura\nCliente · Empresa · Concepto"]
        DOM_WFLOW["Workflow\nWorkflow · WorkflowAuditLog\nAgentConversation"]
        DOM_PANEL["Panel Builder\nPanel · PanelField\nPanelRelation · PanelTable\nBuilderSchema"]
    end

    HTTP --> SVC
    SVC --> DOMAIN
```


---

## 8. Broadcasting — Eventos en Tiempo Real

```mermaid
graph LR
    subgraph EVENTS["Events (app/Events/) — ShouldBroadcast"]
        direction TB
        E1["TaxiBookingCreated\nTaxiBookingUpdated\nTaxiBookingDeleted\nTaxiBookingStatusUpdated\nTaxiBookingDriverAssigned\nTaxiBookingVehicleAssigned\nTaxiServiceUpdated"]
        E2["TripCreated\nTripRequested\nTripStatusChanged\nTripCancelled"]
        E3["DriverLocationUpdated\nDriverAvailabilityChanged"]
        E4["VehicleCreated\nVehicleUpdated\nVehicleTypeUpdated"]
    end

    subgraph CHANNELS["Canales Privados"]
        CH1["taxi-bookings.{id}"]
        CH2["user.{userId}"]
        CH3["App.Models.User.{id}"]
    end

    subgraph REVERB_SRV["Laravel Reverb"]
        RBSRV["WebSocket Server :8080\nTLS-ready\nScaling: Redis (desactivado)\nDev mode: log driver"]
    end

    subgraph LISTENERS["Listeners Frontend"]
        LW_DASH["Livewire Dashboard\nFilament Widgets\nRealtime updates"]
        TAXI_MAP["Mapa taxi\nGPS tracking live"]
    end

    EVENTS --> CHANNELS
    CHANNELS --> REVERB_SRV
    REVERB_SRV --> LISTENERS
```


---

## 9. Panel Filament — Recursos y Páginas

```mermaid
mindmap
  root((Filament /admin))
    Nova Hub
      NovaBusiness
      NovaAiProfile
      NovaAiKnowledge
      NovaWhatsappChannel
      NovaIntentRule
      NovaCrossSellingRule
      NovaIntentToServerMapping
      NovaModule
    MCP
      ServerResource
      ToolResource
      ResourceResource
      PromptResource
      McpDashboard
      McpBusinessHub
      McpInspectorPage
      McpLogViewerPage
      ToolTesterPage
    Reservas
      TaxiBookingResource
      TaxiServiceResource
      TaxiRouteResource
      TaxiTransferBookingResource
      TourBookingResource
      HotelBooking
      RestaurantBooking
      RentalResource
      ExternalBookingResource
      PublicBookingRequestResource
    Catálogo
      TourAdmin Resources
      HotelResource
      RestaurantResource
      LocationResource
      TransferTariffResource
    Integraciones
      ExternalSourceResource
      ExternalOrderResource
      ExternalPaymentResource
      ExternalCatalogItemResource
    Facturación
      FacturaResource
      ClienteResource
      EmpresaResource
    Ajustes
      AdminResource
      DriverResource
      VehicleResource
      AvailabilitySlotResource
      PaymentSettingsPage
    Panel Builder
      PanelResource
      PanelFieldResource
      PanelRelationResource
      PanelTableResource
      PanelBuilderDashboard
    Workflows
      AgentWorkflows
      WorkflowChatPage
      WorkflowPanelManager
    IA
      ServerChatPage
      RentalCalendarPage
```


---

## 10. Frontend Stack

```mermaid
graph TB
    subgraph VITE["Vite 8 Build Pipeline"]
        CSS_APP["resources/css/app.css\n→ Tailwind v4 (público)"]
        JS_APP["resources/js/app.js\n→ Alpine.js v3 + plugins\n(anchor, collapse, focus)\n+ Livewire v4 + Volt"]
        JS_FRONT["resources/js/front.js\n→ turista / /explore"]
        CSS_FIL["resources/css/filament/admin/theme.css\n→ tema rojo Filament"]
    end

    subgraph UI_LIBS["Librerías UI"]
        FLUX["Flux UI Free + Pro v2\ncomponentes reactivos Livewire"]
        BLATUI["BlatUI v1.15\nshadcn/ui para Blade\nresources/views/components/ui/"]
        APEX["ApexCharts v5\ngráficas dashboard"]
        TABLER["Blade Tabler Icons v3\nTablerIcon enum custom"]
        HEROICONS["Blade Heroicons v2"]
    end

    subgraph PAGES["Páginas Públicas (Livewire)"]
        HP["HomePage /ride"]
        EXPLR["PublicExplore /explore\n+ /places + /availability"]
        BOOK["BookingPage /offers/{slug}/booking"]
        TAXI_CO["PublicTaxiRouteCheckout\n/taxi-routes/checkout/{token}"]
        BIZ_ON["BusinessOnboarding\n/business/onboarding"]
        SVC_WIZ["ServiceSubmissionWizard\n/submit-service"]
    end

    VITE --> UI_LIBS
    UI_LIBS --> PAGES
```


---

## 11. Infraestructura Local y Entorno

```mermaid
graph TB
    subgraph LOCAL["💻 Entorno Local — macOS + Laravel Herd"]
        HERD_SRV["Laravel Herd\nhttps://novahubmcp.test (HTTPS auto)\nPHP 8.4"]
        MYSQL["MySQL\nhost: 127.0.0.1:3306\ndb: mcp_studio2026"]
        PGSQL["PostgreSQL\ndb: filament_agentic_chatbot\nRAG / pgvector"]
        REDIS_OFF["Redis (configurado, no activo)\n→ para scaling Reverb futuro"]
        MAILPIT["Mailpit\nlocalhost:2525\n(email dev)"]
        TINKER["Tinkerwell\nbinario MCP ARM64"]
        TBPLUS_LOCAL["TablePlus\nMCP Access Token"]
        OLLAMA_LAN["Ollama LAN\nqwen3:4b\n192.168.1.130:11434"]
        SIRVO_LAN["Sirvo Restaurante\n192.168.1.50:3000"]
    end

    subgraph CLOUD_DEPLOY["☁️ Despliegue — Laravel Cloud"]
        LC["cloud.laravel.com\nTarget deployment\n(no configurado aún)"]
    end

    subgraph DEV_TOOLS["🛠️ Dev Tools"]
        BOOST["laravel/boost v2.4\nMCP server docs/tools"]
        BRAIN["laramint/laravel-brain\nDeep analysis"]
        BLUEPRINT["filament/blueprint v2.1\nFilament code gen"]
        PAIL["laravel/pail v1.2\nTail logs"]
        PINT["laravel/pint v1.24\nCode formatter"]
        SAIL["laravel/sail v1.41\n(disponible, no en uso activo)"]
        PHPUNIT["phpunit/phpunit v11.5\ntests/Feature/ + tests/Unit/"]
    end

    HERD_SRV --> MYSQL
    HERD_SRV --> PGSQL
    HERD_SRV --> MAILPIT
    HERD_SRV --> OLLAMA_LAN
    HERD_SRV --> SIRVO_LAN
    HERD_SRV -.->|"futuro"| CLOUD_DEPLOY
```


---

## 12. Artisan Commands — Mapa de Operaciones

```mermaid
mindmap
  root((php artisan))
    Nova/MCP
      nova:register-external-integrations
      nova:register-whatsapp-cloud
      nova:sync-magento
      nova:sync-woo-latepoint
      nova:orchestrate-demo
      nova:integration-check
      nova:seed-knowledge
      nova:seed-tariffs
      nova:seed-taxi
      nova:seed-transfer
      nova:whatsapp-cloud-diagnostics
      nova:whatsapp-webhook-demo
      import-nova-workflows
    Magento
      nova:magento-issue-admin-token
      sync:magento-invoices
      sync:magento-orders
      sync:single-order
    Portal
      portal:backup
      portal:sync
      portal:sync-advanced
      portal:validate
      portal:optimize
    Data
      materialize-remote-bookings
      register-external-sources
      expire-rentals
      sync-hoteles-from-api
      audit-taxista-duplicates
      merge-taxista-duplicates
      import-taxistas-from-pdf
      normalize-user-licencias
    API/Auth
      generate-api-token
      manage-api-tokens
    Misc
      capture-screenshots
```


---

## 13. Diagrama C4 — Visión de Alto Nivel (Eraser.io compatible)

```
// Pega este código en https://app.eraser.io → New Diagram → Cloud Architecture

title Nova MCP HUB — System Architecture

// External Users
User [icon: user, color: blue]
BusinessOwner [icon: user, color: green]
AIAgent [icon: cpu, color: purple] {
  Cursor
  ClaudeDesktop
  VSCode
}

// Entry Points
WhatsApp [icon: message-square, color: green]
PublicWeb [icon: globe, color: blue] {
  "/explore marketplace"
  "/ai-bot chat"
  "/ride taxi"
}

// Nova Core
NovaHub [icon: server, color: red] {
  Laravel12 [icon: code, color: red] {
    FilamentAdmin [icon: layout, color: red]
    LivewireVolt [icon: zap, color: orange]
    LaravelAI [icon: cpu, color: purple]
    LaravelMCP [icon: link, color: blue]
    LaravelReverb [icon: radio, color: yellow]
    QueueWorker [icon: layers, color: gray]
  }
  MySQL [icon: database, color: blue]
  PostgreSQL_pgvector [icon: database, color: teal]
}

// MCP Servers Connected
MCPNetwork [icon: network, color: purple] {
  laravel_boost [icon: tool]
  filamentphp_mcp [icon: tool]
  tinkerwell [icon: tool]
  taxilanz_wordpress [icon: wordpress]
  lageria_wordpress [icon: wordpress]
  magento2_lanzaloe [icon: shopping-cart]
  sirvo_restaurant [icon: coffee]
  context7_docs [icon: book]
  eraser_diagrams [icon: pen-tool]
  miro_boards [icon: trello]
  tableplus_db [icon: database]
}

// External Systems
ExternalPlatforms [icon: cloud, color: orange] {
  WooCommerce [icon: shopping-cart]
  LatePoint [icon: calendar]
  Magento2 [icon: shopping-bag]
  WordPressMultisite [icon: globe]
}

// Payment Gateways
Payments [icon: credit-card, color: green] {
  Redsys_TPV [icon: credit-card]
  Przelewy24 [icon: credit-card]
}

// AI Providers
AIProviders [icon: cpu, color: purple] {
  OpenAI [icon: zap]
  Anthropic [icon: cpu]
  Ollama_Local [icon: server]
}

// Connections
User --> WhatsApp
User --> PublicWeb
BusinessOwner --> NovaHub.Laravel12.FilamentAdmin
AIAgent --> NovaHub.Laravel12.LaravelMCP

WhatsApp --> NovaHub
PublicWeb --> NovaHub

NovaHub.Laravel12.LaravelMCP --> MCPNetwork
NovaHub.Laravel12.LaravelAI --> AIProviders
NovaHub.Laravel12.LaravelReverb --> User : "WebSocket realtime"
NovaHub --> ExternalPlatforms : "sync bidireccional"
NovaHub --> Payments : "checkout"
NovaHub --> NovaHub.MySQL
NovaHub --> NovaHub.PostgreSQL_pgvector : "RAG embeddings"
```


---

## 14. Resumen — Inventario de Componentes

| Capa | Tecnología | Detalle |
|------|-----------|---------|
| **Framework** | Laravel 12 / PHP 8.4 | bootstrap/app.php streamlined |
| **Admin UI** | Filament v5 | SPA, 20+ directorios de recursos, 2 paneles |
| **Frontend** | Livewire v4 + Volt + Flux UI Pro + BlatUI | Vite 8, Tailwind v4, Alpine.js v3 |
| **MCP Server** | laravel/mcp v0.7 | Servers dinámicos desde DB, DynamicTool/Resource/Prompt |
| **MCP Client** | 15 servidores externos | Tinkerwell, Filament, Magento, WP, Sirvo, Eraser, Miro... |
| **IA / Agents** | laravel/ai v0.6 | 4 agentes: Hub, Intent, BookingExtraction, Response |
| **LLMs** | OpenAI + Anthropic + Ollama | GPT-3.5-turbo, Claude, qwen3:4b local LAN |
| **RAG** | pgvector (PostgreSQL) | 1536 dims, NovaAiKnowledge con auto-embed Observer |
| **WhatsApp** | Meta Cloud API | webhook verify+receive, transcripción de audio |
| **Broadcasting** | Laravel Reverb :8080 | 16 eventos broadcast, canales privados, TLS-ready |
| **Queue** | Database driver | jobs, failed_jobs, job_batches en MySQL |
| **Pagos** | Redsys TPV (ES) + Przelewy24 (PL) | test/sandbox mode |
| **Sync Externo** | Magento2, WooCommerce, LatePoint, WordPress | REST APIs + MCP-remote |
| **Geo** | Geoapify | geocoding, routing, places, transfer-route |
| **DB principal** | MySQL · mcp_studio2026 | 150+ migraciones, 100+ modelos |
| **DB RAG** | PostgreSQL · filament_agentic_chatbot | pgvector |
| **Serving local** | Laravel Herd | https://novahubmcp.test (HTTPS auto) |
| **Deploy target** | Laravel Cloud | pendiente de configurar |
| **Testing** | PHPUnit v11 | tests/Feature/ + tests/Unit/ |
| **Code style** | Laravel Pint v1.24 | --dirty --format agent |
| **Dev tools** | laravel/boost, filament/blueprint, laravel-brain | MCP-powered |

---

*Archivo generado automáticamente. Actualizar con cada cambio estructural significativo.*

```mermaid
graph TD
    %% Configuración de Estilos Visuales Avanzados
    classDef capaVisual fill:#e0f7fa,stroke:#00acc1,stroke-width:2px,color:#000;
    classDef capaCore fill:#ede7f6,stroke:#5e35b1,stroke-width:3px,color:#000,font-weight:bold;
    classDef capaMcpExt fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#000;
    classDef capaModelos fill:#e8f5e9,stroke:#43a047,stroke-width:2px,color:#000;
    classDef canalEntrada fill:#ffffff,stroke:#757575,stroke-width:1px,color:#333,stroke-dasharray: 5 5;

    %% --------------------------------------------------------
    %% CANALES DE ENTRADA (Interfaces Conversacionales)
    %% --------------------------------------------------------
    subgraph Canales [Canales de Entrada / Interfaz Conversacional]
        CH_WA[WhatsApp Cloud API]
        CH_WD[Widget Web Embed]
        CH_TG[Telegram / Slack]
        CH_FL[Filament ServerChat]
    end
    class CH_WA,CH_WD,CH_TG,CH_FL canalEntrada;

    %% --------------------------------------------------------
    %% 1. NOVA AI: AGENTIC CHATBOT LAYER (Flujo No-Code)
    %% --------------------------------------------------------
    subgraph Nova_AI [1. Nova AI: Agentic Chatbot Layer]
        direction TB
        PA[Parent Agent Runtime]
        
        subgraph Builder [Visual Workflow Builder]
            W_Trig[Trigger: Recibe Mensaje] --> W_Input[Collect Input]
            
            %% Nodos MCP Personalizados
            subgraph Nodos_MCP [Nodos MCP Personalizados]
                N_Intent[MCP Intent Detection Node]
                N_Route[MCP Routing Node]
                N_Norm[MCP Normalization Node]
                N_Reg[MCP Registration Node]
                N_Cross[MCP Cross-Selling Node]
                N_Know[MCP Knowledge Search Node]
            end
        end
    end
    class PA,W_Trig,W_Input,N_Intent,N_Route,N_Norm,N_Reg,N_Cross,N_Know capaVisual;

    %% --------------------------------------------------------
    %% 2. NOVA MCP CORE ROUTER & NORMALIZER (Único conocedor de Estructuras)
    %% --------------------------------------------------------
    subgraph Nova_MCP [2. Nova MCP: Core Router & Normalizer]
        direction TB
        R_Intent{Engine: Detección Intención<br>nova_intent_rules}
        R_Router[Smart Router Engine<br>nova_intent_to_server_mapping]
        R_Norm[Response Normalizer]
        R_RegService[Filament Registration Service]
    end
    class R_Intent,R_Router,R_Norm,R_RegService capaCore;

    %% --------------------------------------------------------
    %% 3. AGENTES IA: MCP SERVERS EXTERNOS (Ciegos al Modelo de Datos)
    %% --------------------------------------------------------
    subgraph MCP_Servers [3. Agentes IA: Servidores MCP Externos]
        direction LR
        S_Sirvo["Sirvo MCP (Restaurantes)<br>▪ create_reservation"]
        S_Geria["La Geria MCP (Bodegas)<br>▪ book_wine_tour"]
        S_Taxi["Taxilanz MCP (Taxis)<br>▪ booking_taxi"]
        S_Lanza["Lanzaloe MCP (E-Commerce)<br>▪ create_order"]
    end
    class S_Sirvo,S_Geria,S_Taxi,S_Lanza capaMcpExt;

    %% --------------------------------------------------------
    %% 4. MODELO DE DATOS RAÍZ: ENTIDADES EN FILAMENT
    %% --------------------------------------------------------
    subgraph Modelo_Datos [4. Persistencia e Infraestructura Filament]
        direction TB
        Root_Biz[NovaBusiness]
        Root_Biz --> M_Serv[NovaService]
        Root_Biz --> M_Know[(NovaAiKnowledge)]
        Root_Biz --> M_Cust[(NovaExternalCustomer)]
        Root_Biz --> M_ExtBook[(nova_external_bookings)]
    end
    class Root_Biz,M_Serv,M_Know,M_Cust,M_ExtBook capaModelos;

    %% --------------------------------------------------------
    %% RELACIONES Y FLUJO OPERATIVO (Ciclo Completo de una Petición)
    %% --------------------------------------------------------
    
    %% Ingesta e Inicio de Workflow
    Canales --> PA
    PA --> W_Trig
    
    %% Ejecución del Workflow pasando por los Nodos personalizados
    W_Input --> N_Intent
    N_Intent --> R_Intent
    
    %% Consulta de Conocimiento desde el Workflow
    N_Know --> M_Know
    
    %% Enrutamiento hacia el Core Nova MCP
    N_Route --> R_Router
    
    %% El Core se alimenta de las capacidades registradas en los Modelos de Datos
    M_Serv -.-> R_Router
    
    %% El Core orquesta los MCP Externos (Ellos ejecutan, no guardan en Nova)
    R_Router --> S_Sirvo
    R_Router --> S_Geria
    R_Router --> S_Taxi
    R_Router --> S_Lanza
    
    %% Retorno de los MCP Externos hacia el Normalizador de Nova
    S_Sirvo & S_Geria & S_Taxi & S_Lanza --> R_Norm
    R_Norm --> N_Norm
    
    %% Registro unificado en Filament (Solo Nova MCP interactúa con la DB)
    N_Reg --> R_RegService
    R_RegService --> M_ExtBook
    R_RegService --> M_Cust
    
    %% Regla de Cross-Selling de vuelta al Workflow visual para responder al cliente
    M_Know -.-> N_Cross
    R_RegService --> N_Cross
    N_Cross --> PA
    PA --> Canales
```

Análisis del Flujo de Información en el Gráfico:Abstracción del Canal (Capa Blanca): Cualquier interacción (WhatsApp, Widget, etc.) es interceptada por el runtime del Parent Agent. Los canales no tocan el código de negocio.Lienzo Visual No-Code (Capa Celeste): El flujo avanza de forma secuencial a través de los Nodos MCP Personalizados. Esta capa es la encargada de dictar la lógica humana del flujo (ej. pedir datos faltantes antes de enviar la petición).El Cerebro Normalizador (Capa Morada): El nodo de enrutamiento delega en Nova MCP. Este bloque es el único que traduce los parámetros del bot hacia los servidores externos y viceversa. Los servidores como Sirvo MCP o Taxilanz MCP devuelven JSON puros que el Response Normalizer transforma para que encajen limpiamente en nova_external_bookings.La Raíz Relacional (Capa Verde): Muestra visualmente el principio del modelo: todo cuelga y depende de NovaBusiness, mapeando dinámicamente sus servicios, sus clientes externos unificados y su base de conocimiento indexada.Para materializar esta infraestructura visual en tu panel de administración, dime:¿Quieres que diseñemos la estructura de migración en Laravel / Filament para la tabla nova_mcp_servers que incluya las columnas de capabilities, transport e instructions?¿Te gustaría ver el código de ejemplo en PHP de cómo NovaResponseNormalizer mapearía una respuesta cruda de Sirvo MCP a la tabla estructurada nova_external_bookings?

```mermaid
graph LR
    %% Configuración Estética Avanzada
    classDef completado fill:#e8f5e9,stroke:#2e7d32,stroke-width:2px,color:#000;
    classDef pendienteAlta fill:#ffebee,stroke:#c62828,stroke-width:3px,color:#b71c1c,font-weight:bold;
    classDef pendienteMedia fill:#fff3e0,stroke:#ef6c00,stroke-width:2px,color:#e65100;
    classDef pendienteBaja fill:#f3e5f5,stroke:#6a1b9a,stroke-width:2px,color:#4a148c;
    classDef core fill:#ede7f6,stroke:#4527a0,stroke-width:3px,color:#000,font-weight:bold;
    classDef base fill:#f5f5f5,stroke:#757575,stroke-width:1px,color:#424242;

    %% --------------------------------------------------------
    %% CAPA 1: FUENTES U ORÍGENES DE DATOS (Por Negocio)
    %% --------------------------------------------------------
    subgraph Fuentes [1. Orígenes de Datos por Negocio]
        direction TB
        subgraph Geria [La Geria]
            G_WP[WordPress DB / API]
            G_WC[WooCommerce API]
            G_LP[LatePoint DB]
        end
        subgraph Taxi [Taxilanz]
            T_WC[WooCommerce API]
            T_AU[Auriga API]
            T_LV[Laravel Visitas]
        end
        subgraph Lanza [Lanzaloe]
            L_MG[Magento 2 API]
            L_LV[Laravel Visitas]
        end
        subgraph Sirvo [Sirvo]
            S_NX[Next.js App]
        end
    end
    class G_WP,G_WC,G_LP,T_WC,L_MG base;
    class T_AU,T_LV,L_LV,S_NX base;

    %% --------------------------------------------------------
    %% CAPA 2: EL CATÁLOGO (Estrategia de Carga e Integración)
    %% --------------------------------------------------------
    subgraph Catatogo [2. Catálogo de Colecciones e Integración]
        direction TB
        subgraph Sync_Lectura [Lectura Rápida / Sincronización Masiva]
            C_DB[DB Directa<br>NovaWooLatePointDatabaseSyncService]:::completado
            C_REST[API REST Directa Sync<br>NovaWooCommerceApiSyncService<br>NovaMagentoApiSyncService]:::completado
            C_LV_Sync[Sync Laravel Visitas/Códigos]:::pendienteBaja
        end
        subgraph Creaciones [Escritura / Creaciones Operativas]
            C_Laragento[LaragentoWrapper<br>Magento Creaciones]:::completado
            C_Sirvo_Cli[SirvoReservationClient]:::completado
            C_MCP_Creaciones[NovaMcpCreationService<br>NovaMcpClient]:::completado
        end
    end

    %% --------------------------------------------------------
    %% CAPA 3: EL NÚCLEO DE PROCESAMIENTO (Host de Negocio)
    %% --------------------------------------------------------
    subgraph Core_Nova [3. Core Orchestrator]
        HUB((Nova MCP Hub<br>Host / Client)):::core
        DB_Config[(DB Config<br>nova_mcp_servers)]:::core
    end
    DB_Config -.-> HUB

    %% --------------------------------------------------------
    %% CAPA 4: ROADMAP DE IMPLEMENTACIÓN (Pendientes)
    %% --------------------------------------------------------
    subgraph Roadmap [4. Roadmap de Desarrollo por Prioridad]
        direction TB
        subgraph P_Alta [ALTA - Bloqueantes Creación]
            P1[Cliente MCP para<br>WordPress / WC / LatePoint]:::pendienteAlta
            P2[Extender magento2-mcp<br>con tool 'create_order']:::pendienteAlta
            P3[Crear TaxilanzAurigaClient<br>para Booking Taxi]:::pendienteAlta
        end
        subgraph P_Media [MEDIA - Mejoras de Infraestructura]
            P4[Crear MCP Server<br>Sirvo Next.js]:::pendienteMedia
            P5[Crear MCP Server<br>Laravel Visitas/Códigos]:::pendienteMedia
        end
    end

    %% --------------------------------------------------------
    %% CONEXIONES Y FLUJO LOGÍSTICO
    %% --------------------------------------------------------
    
    %% Mapeo de Fuentes a Estrategias de Lectura (Completados)
    G_WP & G_LP --> C_DB
    G_WC & T_WC & L_MG --> C_REST
    
    %% Mapeo de Fuentes a Estrategias de Lectura (Pendientes)
    T_LV & L_LV --> C_LV_Sync
    
    %% Conexión de Lecturas al HUB de Nova
    C_DB & C_REST & C_LV_Sync --> HUB
    
    %% Conexión de Flujos de Creación / Escritura al HUB
    HUB --> C_Laragento
    HUB --> C_Sirvo_Cli
    HUB --> C_MCP_Creaciones
    
    %% Direccionamiento del HUB hacia los Pendientes del Roadmap
    C_MCP_Creaciones --> P1
    L_MG -.-> P2
    T_AU --> P3
    S_NX --> P4
    T_LV & L_LV -.-> P5

    %% Inyecciones a las configuraciones de Base de Datos
    P1 -.-> DB_Config
    P4 -.-> DB_Config
```

