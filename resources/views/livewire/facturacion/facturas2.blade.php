   
@php
    $user = ['name' => 'shadcn', 'email' => 'm@example.com', 'avatar' => ''];

    $navMain22 = [
        ['title' => 'Facturas', 'icon' => 'layout-dashboard'],
        ['title' => 'Clientes', 'icon' => 'users'],
        ['title' => 'Conceptos', 'icon' => 'list-todo'],
        ['title' => 'Empresas', 'icon' => 'folder'],
    ];

    $documents = [
        ['name' => 'Data Library', 'icon' => 'database'],
        ['name' => 'Reports', 'icon' => 'file-chart-column'],
        ['name' => 'Word Assistant', 'icon' => 'file-text'],
    ];

    $navSecondary = [
        ['title' => 'Settings', 'icon' => 'settings'],
        ['title' => 'Get Help', 'icon' => 'circle-help'],
        ['title' => 'Search', 'icon' => 'search'],
    ];

    $cards = [
        ['desc' => 'Total Revenue', 'value' => '$1,250.00', 'badge' => '+12.5%', 'up' => true, 'line' => 'Trending up this month', 'sub' => 'Visitors for the last 6 months'],
        ['desc' => 'New Customers', 'value' => '1,234', 'badge' => '-20%', 'up' => false, 'line' => 'Down 20% this period', 'sub' => 'Acquisition needs attention'],
        ['desc' => 'Active Accounts', 'value' => '45,678', 'badge' => '+12.5%', 'up' => true, 'line' => 'Strong user retention', 'sub' => 'Engagement exceed targets'],
        ['desc' => 'Growth Rate', 'value' => '4.5%', 'badge' => '+4.5%', 'up' => true, 'line' => 'Steady performance increase', 'sub' => 'Meets growth projections'],
    ];

    $tableData = [
        ['id'=>1,'header'=>'Cover page','type'=>'Cover page','status'=>'In Process','target'=>'18','limit'=>'5','reviewer'=>'Eddie Lake'],
        ['id'=>2,'header'=>'Table of contents','type'=>'Table of contents','status'=>'Done','target'=>'29','limit'=>'24','reviewer'=>'Eddie Lake'],
        ['id'=>3,'header'=>'Executive summary','type'=>'Narrative','status'=>'Done','target'=>'10','limit'=>'13','reviewer'=>'Eddie Lake'],
        ['id'=>4,'header'=>'Technical approach','type'=>'Narrative','status'=>'Done','target'=>'27','limit'=>'23','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>5,'header'=>'Design','type'=>'Narrative','status'=>'In Process','target'=>'2','limit'=>'16','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>6,'header'=>'Capabilities','type'=>'Narrative','status'=>'In Process','target'=>'20','limit'=>'8','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>7,'header'=>'Integration with existing systems','type'=>'Narrative','status'=>'In Process','target'=>'19','limit'=>'21','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>8,'header'=>'Innovation and Advantages','type'=>'Narrative','status'=>'Done','target'=>'25','limit'=>'26','reviewer'=>'Assign reviewer'],
        ['id'=>9,'header'=>"Overview of EMR's Innovative Solutions",'type'=>'Technical content','status'=>'Done','target'=>'7','limit'=>'23','reviewer'=>'Assign reviewer'],
        ['id'=>10,'header'=>'Advanced Algorithms and Machine Learning','type'=>'Narrative','status'=>'Done','target'=>'30','limit'=>'28','reviewer'=>'Assign reviewer'],
        ['id'=>11,'header'=>'Adaptive Communication Protocols','type'=>'Narrative','status'=>'Done','target'=>'9','limit'=>'31','reviewer'=>'Assign reviewer'],
        ['id'=>12,'header'=>'Advantages Over Current Technologies','type'=>'Narrative','status'=>'Done','target'=>'12','limit'=>'0','reviewer'=>'Assign reviewer'],
        ['id'=>13,'header'=>'Past Performance','type'=>'Narrative','status'=>'Done','target'=>'22','limit'=>'33','reviewer'=>'Assign reviewer'],
        ['id'=>14,'header'=>'Customer Feedback and Satisfaction Levels','type'=>'Narrative','status'=>'Done','target'=>'15','limit'=>'34','reviewer'=>'Assign reviewer'],
        ['id'=>15,'header'=>'Implementation Challenges and Solutions','type'=>'Narrative','status'=>'Done','target'=>'3','limit'=>'35','reviewer'=>'Assign reviewer'],
        ['id'=>16,'header'=>'Security Measures and Data Protection Policies','type'=>'Narrative','status'=>'In Process','target'=>'6','limit'=>'36','reviewer'=>'Assign reviewer'],
        ['id'=>17,'header'=>'Scalability and Future Proofing','type'=>'Narrative','status'=>'Done','target'=>'4','limit'=>'37','reviewer'=>'Assign reviewer'],
        ['id'=>18,'header'=>'Cost-Benefit Analysis','type'=>'Plain language','status'=>'Done','target'=>'14','limit'=>'38','reviewer'=>'Assign reviewer'],
        ['id'=>19,'header'=>'User Training and Onboarding Experience','type'=>'Narrative','status'=>'Done','target'=>'17','limit'=>'39','reviewer'=>'Assign reviewer'],
        ['id'=>20,'header'=>'Future Development Roadmap','type'=>'Narrative','status'=>'Done','target'=>'11','limit'=>'40','reviewer'=>'Assign reviewer'],
        ['id'=>21,'header'=>'System Architecture Overview','type'=>'Technical content','status'=>'In Process','target'=>'24','limit'=>'18','reviewer'=>'Maya Johnson'],
        ['id'=>22,'header'=>'Risk Management Plan','type'=>'Narrative','status'=>'Done','target'=>'15','limit'=>'22','reviewer'=>'Carlos Rodriguez'],
        ['id'=>23,'header'=>'Compliance Documentation','type'=>'Legal','status'=>'In Process','target'=>'31','limit'=>'27','reviewer'=>'Sarah Chen'],
        ['id'=>24,'header'=>'API Documentation','type'=>'Technical content','status'=>'Done','target'=>'8','limit'=>'12','reviewer'=>'Raj Patel'],
        ['id'=>25,'header'=>'User Interface Mockups','type'=>'Visual','status'=>'In Process','target'=>'19','limit'=>'25','reviewer'=>'Leila Ahmadi'],
        ['id'=>26,'header'=>'Database Schema','type'=>'Technical content','status'=>'Done','target'=>'22','limit'=>'20','reviewer'=>'Thomas Wilson'],
        ['id'=>27,'header'=>'Testing Methodology','type'=>'Technical content','status'=>'In Process','target'=>'17','limit'=>'14','reviewer'=>'Assign reviewer'],
        ['id'=>28,'header'=>'Deployment Strategy','type'=>'Narrative','status'=>'Done','target'=>'26','limit'=>'30','reviewer'=>'Eddie Lake'],
        ['id'=>29,'header'=>'Budget Breakdown','type'=>'Financial','status'=>'In Process','target'=>'13','limit'=>'16','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>30,'header'=>'Market Analysis','type'=>'Research','status'=>'Done','target'=>'29','limit'=>'32','reviewer'=>'Sophia Martinez'],
        ['id'=>31,'header'=>'Competitor Comparison','type'=>'Research','status'=>'In Process','target'=>'21','limit'=>'19','reviewer'=>'Assign reviewer'],
        ['id'=>32,'header'=>'Maintenance Plan','type'=>'Technical content','status'=>'Done','target'=>'16','limit'=>'23','reviewer'=>'Alex Thompson'],
        ['id'=>33,'header'=>'User Personas','type'=>'Research','status'=>'In Process','target'=>'27','limit'=>'24','reviewer'=>'Nina Patel'],
        ['id'=>34,'header'=>'Accessibility Compliance','type'=>'Legal','status'=>'Done','target'=>'18','limit'=>'21','reviewer'=>'Assign reviewer'],
        ['id'=>35,'header'=>'Performance Metrics','type'=>'Technical content','status'=>'In Process','target'=>'23','limit'=>'26','reviewer'=>'David Kim'],
        ['id'=>36,'header'=>'Disaster Recovery Plan','type'=>'Technical content','status'=>'Done','target'=>'14','limit'=>'17','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>37,'header'=>'Third-party Integrations','type'=>'Technical content','status'=>'In Process','target'=>'25','limit'=>'28','reviewer'=>'Eddie Lake'],
        ['id'=>38,'header'=>'User Feedback Summary','type'=>'Research','status'=>'Done','target'=>'20','limit'=>'15','reviewer'=>'Assign reviewer'],
        ['id'=>39,'header'=>'Localization Strategy','type'=>'Narrative','status'=>'In Process','target'=>'12','limit'=>'19','reviewer'=>'Maria Garcia'],
        ['id'=>40,'header'=>'Mobile Compatibility','type'=>'Technical content','status'=>'Done','target'=>'28','limit'=>'31','reviewer'=>'James Wilson'],
        ['id'=>41,'header'=>'Data Migration Plan','type'=>'Technical content','status'=>'In Process','target'=>'19','limit'=>'22','reviewer'=>'Assign reviewer'],
        ['id'=>42,'header'=>'Quality Assurance Protocols','type'=>'Technical content','status'=>'Done','target'=>'30','limit'=>'33','reviewer'=>'Priya Singh'],
        ['id'=>43,'header'=>'Stakeholder Analysis','type'=>'Research','status'=>'In Process','target'=>'11','limit'=>'14','reviewer'=>'Eddie Lake'],
        ['id'=>44,'header'=>'Environmental Impact Assessment','type'=>'Research','status'=>'Done','target'=>'24','limit'=>'27','reviewer'=>'Assign reviewer'],
        ['id'=>45,'header'=>'Intellectual Property Rights','type'=>'Legal','status'=>'In Process','target'=>'17','limit'=>'20','reviewer'=>'Sarah Johnson'],
        ['id'=>46,'header'=>'Customer Support Framework','type'=>'Narrative','status'=>'Done','target'=>'22','limit'=>'25','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>47,'header'=>'Version Control Strategy','type'=>'Technical content','status'=>'In Process','target'=>'15','limit'=>'18','reviewer'=>'Assign reviewer'],
        ['id'=>48,'header'=>'Continuous Integration Pipeline','type'=>'Technical content','status'=>'Done','target'=>'26','limit'=>'29','reviewer'=>'Michael Chen'],
        ['id'=>49,'header'=>'Regulatory Compliance','type'=>'Legal','status'=>'In Process','target'=>'13','limit'=>'16','reviewer'=>'Assign reviewer'],
        ['id'=>50,'header'=>'User Authentication System','type'=>'Technical content','status'=>'Done','target'=>'28','limit'=>'31','reviewer'=>'Eddie Lake'],
        ['id'=>51,'header'=>'Data Analytics Framework','type'=>'Technical content','status'=>'In Process','target'=>'21','limit'=>'24','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>52,'header'=>'Cloud Infrastructure','type'=>'Technical content','status'=>'Done','target'=>'16','limit'=>'19','reviewer'=>'Assign reviewer'],
        ['id'=>53,'header'=>'Network Security Measures','type'=>'Technical content','status'=>'In Process','target'=>'29','limit'=>'32','reviewer'=>'Lisa Wong'],
        ['id'=>54,'header'=>'Project Timeline','type'=>'Planning','status'=>'Done','target'=>'14','limit'=>'17','reviewer'=>'Eddie Lake'],
        ['id'=>55,'header'=>'Resource Allocation','type'=>'Planning','status'=>'In Process','target'=>'27','limit'=>'30','reviewer'=>'Assign reviewer'],
        ['id'=>56,'header'=>'Team Structure and Roles','type'=>'Planning','status'=>'Done','target'=>'20','limit'=>'23','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>57,'header'=>'Communication Protocols','type'=>'Planning','status'=>'In Process','target'=>'15','limit'=>'18','reviewer'=>'Assign reviewer'],
        ['id'=>58,'header'=>'Success Metrics','type'=>'Planning','status'=>'Done','target'=>'30','limit'=>'33','reviewer'=>'Eddie Lake'],
        ['id'=>59,'header'=>'Internationalization Support','type'=>'Technical content','status'=>'In Process','target'=>'23','limit'=>'26','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>60,'header'=>'Backup and Recovery Procedures','type'=>'Technical content','status'=>'Done','target'=>'18','limit'=>'21','reviewer'=>'Assign reviewer'],
        ['id'=>61,'header'=>'Monitoring and Alerting System','type'=>'Technical content','status'=>'In Process','target'=>'25','limit'=>'28','reviewer'=>'Daniel Park'],
        ['id'=>62,'header'=>'Code Review Guidelines','type'=>'Technical content','status'=>'Done','target'=>'12','limit'=>'15','reviewer'=>'Eddie Lake'],
        ['id'=>63,'header'=>'Documentation Standards','type'=>'Technical content','status'=>'In Process','target'=>'27','limit'=>'30','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>64,'header'=>'Release Management Process','type'=>'Planning','status'=>'Done','target'=>'22','limit'=>'25','reviewer'=>'Assign reviewer'],
        ['id'=>65,'header'=>'Feature Prioritization Matrix','type'=>'Planning','status'=>'In Process','target'=>'19','limit'=>'22','reviewer'=>'Emma Davis'],
        ['id'=>66,'header'=>'Technical Debt Assessment','type'=>'Technical content','status'=>'Done','target'=>'24','limit'=>'27','reviewer'=>'Eddie Lake'],
        ['id'=>67,'header'=>'Capacity Planning','type'=>'Planning','status'=>'In Process','target'=>'21','limit'=>'24','reviewer'=>'Jamik Tashpulatov'],
        ['id'=>68,'header'=>'Service Level Agreements','type'=>'Legal','status'=>'Done','target'=>'26','limit'=>'29','reviewer'=>'Assign reviewer'],
    ];

    $versions = ['1.0.1', '1.1.0-alpha', '2.0.0-beta1'];
    $navMain = [
        ['title' => 'Getting Started', 'items' => [
            ['title' => 'Installation'], ['title' => 'Project Structure'],
        ]],
        ['title' => 'Build Your Application', 'items' => [
            ['title' => 'Routing'], ['title' => 'Data Fetching', 'isActive' => true],
            ['title' => 'Rendering'], ['title' => 'Caching'], ['title' => 'Styling'],
            ['title' => 'Optimizing'], ['title' => 'Configuring'], ['title' => 'Testing'],
            ['title' => 'Authentication'], ['title' => 'Deploying'], ['title' => 'Upgrading'],
            ['title' => 'Examples'],
        ]],
        ['title' => 'API Reference', 'items' => [
            ['title' => 'Components'], ['title' => 'File Conventions'], ['title' => 'Functions'],
            ['title' => 'next.config.js Options'], ['title' => 'CLI'], ['title' => 'Edge Runtime'],
        ]],
        ['title' => 'Architecture', 'items' => [
            ['title' => 'Accessibility'], ['title' => 'Fast Refresh'], ['title' => 'Next.js Compiler'],
            ['title' => 'Supported Browsers'], ['title' => 'Turbopack'],
        ]],
        ['title' => 'Community', 'items' => [
            ['title' => 'Contribution Guide'],
        ]],
    ];
@endphp

<div>



    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white border border-slate-100 p-6 rounded-2xl text-slate-800 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Facturas</h1>
            <p class="text-sm text-slate-500 mt-1">Listado inteligente de facturación, búsqueda, filtros dinámicos y editor en vivo en formato Side-In.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <input
                    type="text"
                    wire:model.debounce.300ms="search"
                    placeholder="Buscar por nº o cliente…"
                    class="h-10 w-64 rounded-xl border border-slate-200 bg-slate-50/50 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all shadow-inner"
                >
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <select
                wire:model="empresaFilter"
                class="h-10 rounded-xl border border-slate-200 bg-slate-50/50 px-4 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
            >
                <option value="">Todas las empresas</option>
                @foreach($this->empresas as $empresa)
                    <option value="{{ $empresa['id'] }}" selected>{{ $empresa['empresa'] }}</option>
                @endforeach
            </select>

            <button
                type="button"
                wire:click="newFactura"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nueva factura
            </button>
        </div>
    </div>

        {{-- LISTADO --}}
        <div class="rounded-md bg-card shadow-sm">
            <div class="border-b border-default-200 px-4 py-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-default-800">Listado</h2>
                {{-- aquí podrías poner filtros rápidos, tags, etc. --}}
            </div>
            <div class="p-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-default-200 text-xs text-default-500">
                    <tr>
                        <th class="py-2 text-left">Nº</th>
                        <th class="py-2 text-left">Fecha</th>
                        <th class="py-2 text-left">Cliente</th>
                        <th class="py-2 text-right">Base</th>
                        <th class="py-2 text-right">Total</th>
                        <th class="py-2 text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-default-100">
                    @forelse($this->facturas as $factura)
                        <tr class="hover:bg-default-50/40">
                            <td class="py-2 text-xs font-mono text-default-700">
                                {{ $factura->codfactura ?? $factura->id }}
                            </td>
                            <td class="py-2 text-sm text-default-700">
                                {{ optional($factura->fechaemitido)->format('d/m/Y') }}
                            </td>
                            <td class="py-2 text-sm">
                                {{ optional($factura->cliente)->nombretotal ?? '—' }}
                            </td>
                            <td class="py-2 text-sm text-right">
                                {{ number_format($factura->baseimponible, 2, ',', '.') }} €
                            </td>
                            <td class="py-2 text-sm text-right font-semibold">
                                {{ number_format($factura->importe, 2, ',', '.') }} €
                            </td>
                            <td class="py-2 text-sm text-right">
                                <button
                                    type="button"
                                    wire:click="editFactura({{ $factura->id }})"
                                    class="inline-flex items-center rounded-md border border-default-300 px-2 py-1 text-xs hover:border-primary hover:text-primary"
                                >
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-sm text-default-500">
                                No hay facturas que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                </div>
            </div>
        </div>
    <div class="py-4 max-w-7xl mx-auto space-y-6">

        @if(session('message'))
            <div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
        @endif

    </div>


    {{-- EDITOR SLIDE-IN --}}
    <div
        x-data="{ open: @entangle('showEditor') }"
        x-cloak
        x-show="open"
        class="fixed inset-0 z-50 flex justify-end"
        aria-labelledby="slide-over-title" role="dialog" aria-modal="true"
    >
        {{-- Backdrop con efecto blur suave --}}
        <div
            x-show="open"
            x-transition:enter="ease-in-out duration-500"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in-out duration-500"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity"
            @click="open = false"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="relative w-screen max-w-2xl bg-slate-50 dark:bg-slate-950 shadow-2xl border-l border-slate-200/80 dark:border-slate-800 flex flex-col h-full"
        >
            {{-- Cabecera del Editor --}}
            <div class="bg-gradient-to-r from-slate-900 to-indigo-950 px-6 py-5 border-b border-slate-200/10 dark:border-slate-800 flex items-center justify-between text-white shadow-sm shrink-0">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-indigo-500/15 rounded-xl text-indigo-300">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold tracking-tight">
                            @if($editingId) Editar Factura @else Crear Factura @endif
                        </h2>
                        <p class="text-xs text-slate-300 mt-0.5">Empresa, cliente, líneas y totales en la misma vista interactiva.</p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    class="rounded-xl p-1.5 text-slate-400 hover:text-white hover:bg-white/10 active:scale-95 transition-all cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Cuerpo del Editor --}}
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                {{-- Bloque 1: Empresa + Fecha --}}
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Empresa emisora</label>
                        <select
                            wire:model="form.empresa_id"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 px-3 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                        >
                            <option value="">Seleccionar empresa…</option>
                            @foreach($this->empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->empresa }}</option>
                            @endforeach
                        </select>
                        @error('form.empresa_id') <p class="text-xs font-medium text-rose-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">

                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Cliente</label>


                       <flux:select wire:model.live="filters.cliente"
                                           
   variant="listbox" placeholder="Cliente..." >
                <flux:select.option value="" wire:model.live="filters.cliente" wire:key="all">Todos</flux:select.option>
                @foreach($this->clientes as $cliente)
                    <flux:select.option value="{{ $cliente['codcliente'] }}" wire:key="{{ $cliente['codcliente'] }}">{{ $cliente['nombretotal'] }}</flux:select.option>
                @endforeach
            </flux:select>
                                </div>

                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Fecha de emisión</label>
                        <input
                            type="date"
                            wire:model="form.fechaemitido"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                        >
                        @error('form.fechaemitido') <p class="text-xs font-medium text-rose-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Bloque 2: Cliente autocomplete + botón nuevo --}}
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-100 dark:border-slate-800/80 shadow-sm space-y-2.5" x-data="{ openDrop: false }">
                    <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Cliente receptor</label>
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
 

            <input
                                type="text"
                                x-on:focus="openDrop = true"
                                x-on:click.outside="openDrop = false"
                                wire:model.live.debounce.300ms="clienteSearch"
                                placeholder="Buscar cliente por nombre o NIF…"
                                class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 pl-9 pr-4 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                            >
                            @if(!empty($clienteSuggestions))
                                <div
                                    x-show="openDrop"
                                    class="absolute mt-1.5 w-full rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xl z-20 overflow-hidden divide-y divide-slate-100 dark:divide-slate-800"
                                >
                                    @foreach($clienteSuggestions as $s)
                                        <button
                                            type="button"
                                            wire:click="selectCliente({{ $s['id'] }})"
                                            class="block w-full px-4 py-2.5 text-left text-xs text-slate-700 dark:text-slate-300 hover:bg-indigo-50/60 dark:hover:bg-slate-800 transition-colors font-medium cursor-pointer"
                                        >
                                            {{ $s['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="openClienteModal"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-800 dark:hover:border-slate-700 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 px-4 text-xs font-semibold active:scale-95 transition-all cursor-pointer shadow-sm"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Nuevo
                        </button>
                    </div>
                    @error('form.cliente_id') <p class="text-xs font-medium text-rose-500 mt-0.5">{{ $message }}</p> @enderror
             
                 <div class="flex flex-wrap items-center gap-3">
        

                    <div class="space-y-4">
                        @foreach($form['lineas'] as $index => $linea)
                            <div class="rounded-2xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/60 p-4 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500/80"></div>
                                <div class="flex items-center justify-between gap-3 border-b border-slate-50 dark:border-slate-800/50 pb-3 mb-3">
                                    {{-- Concepto Search --}}
                                    <div class="flex-1 space-y-1" x-data="{ openDrop: false }">
                                        <label class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Concepto / descripción</label>
                                        <div class="flex gap-2">
                                            <div class="relative flex-1">
                                                <input
                                                    type="text"
                                                    wire:model.live.debounce.250ms="form.lineas.{{ $index }}.descripcion"
                                                    placeholder="Descripción detallada o selección de concepto…"
                                                    class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900 px-3 py-2 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                                                >
                                            </div>
                                            <button
                                                type="button"
                                                x-on:click="openDrop = true"
                                                wire:click="openConceptoSearch({{ $index }})"
                                                class="inline-flex items-center rounded-xl border border-slate-200 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-800 dark:hover:border-slate-700 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 px-3 text-[11px] font-semibold active:scale-95 transition-all cursor-pointer shadow-sm"
                                            >
                                                Buscar
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="openConceptoModal({{ $index }})"
                                                class="inline-flex items-center gap-1 rounded-xl border border-slate-200 hover:border-slate-400 hover:bg-slate-50 dark:border-slate-800 dark:hover:border-slate-700 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 px-3 text-[11px] font-semibold active:scale-95 transition-all cursor-pointer shadow-sm"
                                            >
                                                + Nuevo
                                            </button>
                                        </div>

                                        {{-- Dropdown conceptos --}}
                                        @if($conceptoLineaIndex === $index && !empty($conceptoSuggestions))
                                            <div
                                                class="absolute mt-1.5 max-h-48 overflow-y-auto rounded-xl border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs shadow-2xl z-30 divide-y divide-slate-100 dark:divide-slate-800 overflow-hidden w-full max-w-md"
                                            >
                                                @foreach($conceptoSuggestions as $opt)
                                                    <button
                                                        type="button"
                                                        wire:click="selectConcepto({{ $opt['id'] }})"
                                                        class="block w-full px-4 py-3 text-left hover:bg-indigo-50/60 dark:hover:bg-slate-850 transition-colors font-medium cursor-pointer"
                                                    >
                                                        <div class="text-slate-800 dark:text-slate-200 font-semibold">{{ $opt['label'] }}</div>
                                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                                            Precio: <span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($opt['precio'], 2, ',', '.') }} €</span> · Unidad: {{ $opt['unidad'] }}
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <button
                                        type="button"
                                        wire:click="removeLinea({{ $index }})"
                                        class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/20 active:scale-90 transition-all self-end cursor-pointer"
                                        title="Eliminar línea"
                                    >
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>

                                {{-- Detalle de importes de línea --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cantidad</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.cantidad"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Precio (€)</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.precio"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Dto. %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.descuento"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unidad</label>
                                        <input
                                            type="text"
                                            wire:model.lazy="form.lineas.{{ $index }}.unidad"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                </div>

                                {{-- IGIC y retención + subtotal e importe --}}
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3 pt-3 border-t border-slate-50 dark:border-slate-800/30">
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">IGIC %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.impuesto"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ret. %</label>
                                        <input
                                            type="number" step="0.01"
                                            wire:model.lazy="form.lineas.{{ $index }}.retenciones"
                                            class="w-full rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/20 dark:bg-slate-900 px-2 py-1.5 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">IGIC Impuesto</label>
                                        <div class="w-full bg-slate-50 dark:bg-slate-900 rounded-lg px-2 py-1.5 text-xs text-slate-500 font-medium border border-slate-100 dark:border-slate-800">
                                            {{ number_format($linea['valorimpuesto'] ?? 0, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <label class="text-[10px] font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Total Línea</label>
                                        <div class="w-full bg-indigo-50/40 dark:bg-slate-900 rounded-lg px-2 py-1.5 text-xs text-indigo-700 dark:text-indigo-400 font-bold border border-indigo-100/50 dark:border-indigo-950/20">
                                            {{ number_format($linea['importe'] ?? 0, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Bloque 4: Resumen económico --}}
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-lg space-y-4">
                    <h4 class="text-xs font-bold text-indigo-300 uppercase tracking-wider">Desglose económico</h4>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center text-slate-300">
                            <span>Base imponible</span>
                            <span class="font-semibold text-white">
                                {{ number_format($form['baseimponible'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-300">
                            <span>Base exenta</span>
                            <span class="font-semibold text-white">
                                {{ number_format($form['baseexenta'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-300">
                            <span>IGIC (7%)</span>
                            <span class="font-semibold text-emerald-400">
                                +{{ number_format($form['impuesto'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-300">
                            <span>Retenciones (15%)</span>
                            <span class="font-semibold text-rose-400">
                                -{{ number_format($form['retenciones'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="border-t border-white/10 my-2 pt-2.5 flex justify-between items-center text-sm">
                            <span class="font-bold text-white uppercase tracking-wider text-xs">Total final factura</span>
                            <span class="font-extrabold text-white text-base">
                                {{ number_format($form['importe'], 2, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pie del Editor --}}
            <div class="border-t border-slate-200 dark:border-slate-800 px-6 py-4 bg-white dark:bg-slate-950 flex items-center justify-between shrink-0 shadow-lg">
                <button
                    type="button"
                    wire:click="$set('showEditor', false)"
                    class="rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-900 active:scale-95 transition-all cursor-pointer border border-transparent hover:border-slate-100"
                >
                    Cancelar
                </button>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        wire:click="save"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        Guardar factura
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1.7fr)_minmax(0,1.1fr)] gap-6">
    {{-- MODAL NUEVO CLIENTE --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showClienteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Nuevo cliente</h2>
                </div>
                <button wire:click="$set('showClienteModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre / Razón social</label>
                    <input type="text" wire:model="nuevoCliente.nombretotal" placeholder="Ej: Lanzaloe, S.L." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">DNI / NIF / CIF</label>
                    <input type="text" wire:model="nuevoCliente.dni" placeholder="Ej: B12345678" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email de contacto</label>
                    <input type="email" wire:model="nuevoCliente.email" placeholder="correo@cliente.com" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" wire:model="nuevoCliente.telefono" placeholder="+34 600 000 000" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Domicilio</label>
                        <input type="text" wire:model="nuevoCliente.domicilio" placeholder="Calle, Nº..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Población</label>
                        <input type="text" wire:model="nuevoCliente.poblacion" placeholder="Ciudad" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showClienteModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="saveNuevoCliente" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                    Guardar cliente
                </button>
            </div>
        </div>
    </div>



    {{-- MODAL NUEVO CONCEPTO --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showConceptoModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Nuevo concepto</h2>
                </div>
                <button wire:click="$set('showConceptoModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre / Concepto</label>
                    <input type="text" wire:model="nuevoConcepto.concepto" placeholder="Ej: Servicios de desarrollo web" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Grupo / Categoría</label>
                        <input type="text" wire:model="nuevoConcepto.grupo" placeholder="Ej: Ingeniería" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Unidad de medida</label>
                        <input type="text" wire:model="nuevoConcepto.unidad" placeholder="Ej: UNID, HORA..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Precio (€)</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.precio" placeholder="0.00" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Descuento %</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.descuento" placeholder="0" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">IGIC %</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.impuesto" placeholder="7" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Retención %</label>
                    <input type="number" step="0.01" wire:model="nuevoConcepto.retenciones" placeholder="15" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showConceptoModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="saveNuevoConcepto" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                    Guardar concepto
                </button>
            </div>
        </div>
    </div>



