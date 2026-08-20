   
@php
    $user = ['name' => 'shadcn', 'email' => 'm@example.com', 'avatar' => ''];

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
        ['title' => 'Facturas', 'items' => [
            ['title' => 'Facturas'], 
            ['title' => 'Clientes'],
        ]],
        
    ];
     $calendars = [
        ['name' => 'My Calendars', 'items' => ['Personal', 'Work', 'Family']],
        ['name' => 'Favorites', 'items' => ['Holidays', 'Birthdays']],
        ['name' => 'Other', 'items' => ['Travel', 'Reminders', 'Deadlines']],
    ];
@endphp

<div>

    <x-ui.sidebar-provider>

           <x-ui.sidebar data-state="collapsed"  data-collapsible="offcanvas" class="hidden sticky top-0 hidden h-svh border-l lg:flex">
            <x-ui.sidebar-header class="border-sidebar-border h-16 border-b">

                        
                                                                                       <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Fecha de emisión</label>
                        <input
                            type="date"
                            wire:model="form.fechaemitido"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 px-3 py-2 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                        >
                        @error('form.fechaemitido') <p class="text-xs font-medium text-rose-500 mt-0.5">{{ $message }}</p> @enderror
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
                                 </div>

            </x-ui.sidebar-header>
            <x-ui.sidebar-content>
                <x-ui.sidebar-group class="px-0">
                    <x-ui.sidebar-group-content>
                        <x-ui.calendar class="[&_[role=gridcell]]:w-[33px]" />
                    </x-ui.sidebar-group-content>
                </x-ui.sidebar-group>
                
                <x-ui.sidebar-separator class="mx-0" />
                @foreach ($calendars as $index => $calendar)
                    <x-ui.sidebar-group class="py-0">
                        <x-ui.collapsible :open="$index === 0" class="group/collapsible" ::data-state="open ? 'open' : 'closed'">
                            <x-ui.sidebar-group-label
                                class="group/label text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground w-full text-sm"
                                x-on:click="open = !open" ::data-state="open ? 'open' : 'closed'" role="button">
                                {{ $calendar['name'] }}
                                <x-lucide-chevron-right class="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                            </x-ui.sidebar-group-label>
                            <x-ui.collapsible-content>
                                <x-ui.sidebar-group-content>
         
                                    <x-ui.sidebar-menu>

                                        @foreach ($calendar['items'] as $i => $item)
                                            <x-ui.sidebar-menu-item>
                                                <x-ui.sidebar-menu-button>
                                                    <div @if ($i < 2) data-active="true" @endif class="group/calendar-item border-sidebar-border text-sidebar-primary-foreground data-[active=true]:border-sidebar-primary data-[active=true]:bg-sidebar-primary flex aspect-square size-4 shrink-0 items-center justify-center rounded-sm border">
                                                        <x-lucide-check class="hidden size-3 group-data-[active=true]/calendar-item:block" />
                                                    </div>
                                                    {{ $item }}
                                                </x-ui.sidebar-menu-button>
                                            </x-ui.sidebar-menu-item>
                                        @endforeach
                                    </x-ui.sidebar-menu>
                                </x-ui.sidebar-group-content>
                            </x-ui.collapsible-content>
                        </x-ui.collapsible>
                    </x-ui.sidebar-group>
                    <x-ui.sidebar-separator class="mx-0" />
                @endforeach
            </x-ui.sidebar-content>
            <x-ui.sidebar-footer>
                <x-ui.sidebar-menu>
                    <x-ui.sidebar-menu-item>
                        <x-ui.sidebar-menu-button>
                            <x-lucide-plus />
                            <span>New Calendar</span>
                        </x-ui.sidebar-menu-button>
                    </x-ui.sidebar-menu-item>
                </x-ui.sidebar-menu>
            </x-ui.sidebar-footer>
        </x-ui.sidebar>
        <x-ui.sidebar-inset>
            
            <header class="bg-background sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b px-4">
                <x-ui.sidebar-trigger class="-ml-1" />

                <x-ui.separator orientation="vertical" class="mr-2 h-4" />
                <x-ui.sidebar-menu-button
                wire:click="newFactura"
            ><svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M9 7V2.221a2 2 0 0 0-.5.365L4.586 6.5a2 2 0 0 0-.365.5H9Zm2 0V2h7a2 2 0 0 1 2 2v6.41A7.5 7.5 0 1 0 10.5 22H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"></path>
              <path fill-rule="evenodd" d="M9 16a6 6 0 1 1 12 0 6 6 0 0 1-12 0Zm6-3a1 1 0 0 1 1 1v1h1a1 1 0 1 1 0 2h-1v1a1 1 0 1 1-2 0v-1h-1a1 1 0 1 1 0-2h1v-1a1 1 0 0 1 1-1Z" clip-rule="evenodd"></path>
            </svg>
                Nueva factura
                        </x-ui.sidebar-menu-button>
            <x-ui.sidebar-menu-button>
                            <x-lucide-plus />
                            <span>Nuevo Cliente</span>
                        </x-ui.sidebar-menu-button>
            <x-ui.sidebar-menu-button>
                            <x-lucide-plus />
                            <span>Nuevo Concepto</span>
                        </x-ui.sidebar-menu-button>                        
                 <div>
        </div>
            </header>
            <div class="flex flex-1 flex-col gap-4 p-4">
        {{-- LISTADO --}}
<div class="grid grid-cols-12 gap-4 bg-white dark:bg-gray-900">
  <div class="col-span-full mx-4 mt-4 items-center justify-between sm:flex">
    <div class="mb-4 sm:mb-0">
      <nav class="mb-4 flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
          <li class="inline-flex items-center">
            <a href="/" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white">
              <svg class="me-2.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M11.3 3.3a1 1 0 0 1 1.4 0l6 6 2 2a1 1 0 0 1-1.4 1.4l-.3-.3V19a2 2 0 0 1-2 2h-3a1 1 0 0 1-1-1v-3h-2v3c0 .6-.4 1-1 1H7a2 2 0 0 1-2-2v-6.6l-.3.3a1 1 0 0 1-1.4-1.4l2-2 6-6Z" clip-rule="evenodd" />
              </svg>
              Home
            </a>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
              </svg>
              <a href="#" class="ms-1 text-sm font-medium text-gray-700 hover:text-primary-700 dark:text-gray-400 dark:hover:text-white md:ms-2">Platform</a>
            </div>
          </li>
          <li aria-current="page">
            <div class="flex items-center">
              <svg class="mx-1 h-4 w-4 text-gray-400 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
              </svg>
              <span class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ms-2">Users</span>
            </div>
          </li>
        </ol>
      </nav>
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">All Users</h1>
    </div>
    <button
      type="button"
      id="createUserButton"
      data-modal-target="createUserAccordionModal"
      data-modal-toggle="createUserAccordionModal"
      class="flex w-full items-center justify-center rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800 sm:w-auto"
    >
      <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-7 7V5" />
      </svg>
      Add new user
    </button>
  </div>
  <div class="relative col-span-full">
    <div class="px-4">
      <div class="grid w-full grid-cols-2 gap-4 pb-4 md:grid-cols-3 xl:grid-cols-6">
        <div class="relative">
          <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
          </div>
          <input
            type="text"
            name="email"
            id="topbar-search"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2 pl-9 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            placeholder="Search for users"
          />
        </div>
                                    <select
                wire:model="empresaFilter"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            >
                              <option selected="">Empresa</option>

                @foreach($this->empresas as $empresa)
                    <option value="{{ $empresa['id'] }}" selected>{{ $empresa['empresa'] }}</option>
                @endforeach
            </select>
        <select
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
                  <option selected="">Cliente</option>

                        @foreach($this->clientes as $cliente)
                    <option value="{{ $cliente['id'] }}" selected>{{ $cliente['nombretotal'] }}</option>
                @endforeach
          </select
        ><select
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Estado</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          </select
        ><select
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-2.5 py-2 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option selected="">Tipo</option>
          <option value="pro">Nova</option>
          <option value="basic">Lava</option></select
        >
      </div>
      <div class="block w-full items-center justify-between border-t py-3 dark:border-gray-800 sm:flex border-gray-200">
        <div class="flex flex-wrap gap-4">
          <div class="flex items-center text-sm font-medium text-gray-900 dark:text-white">Ordenar:</div>
          <div class="flex items-center">
            <input
              id="all-users"
              type="radio"
              value=""
              name="show-only"
              checked=""
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="all-users" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Fecha</label>
          </div>
          <div class="flex items-center">
            <input
              id="sort-role"
              type="radio"
              value=""
              name="show-only"
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-role" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Cliente</label>
          </div>
          <div class="flex items-center">
            <input
              id="sort-type"
              type="radio"
              value=""
              name="show-only"
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-type" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Tipo</label>
          </div>
          <div class="flex items-center">
            <input
              id="sort-status"
              type="radio"
              value=""
              name="show-only"
              class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
            />
            <label for="sort-status" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Estado</label>
          </div>
          
        </div>
        <div class="mt-4 sm:mt-0">
          <button
            id="actionsDropdownButton"
            data-dropdown-toggle="actionsDropdown"
            class=" flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 sm:w-auto"
            type="button"
          >
            Actions
            <svg class="-me-0.5 ms-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
            </svg>
          </button>
          <div id="actionsDropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
            <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="actionsDropdownButton">
              <li>
                <button 
                type="button" 
                id="archiveAllUsersButton"
                data-modal-target="archiveAllUsersModal"
                data-modal-toggle="archiveAllUsersModal"
                class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                  <svg class="w-4 h-4 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd"/>
                    <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z"/>
                  </svg>
                  Archive all
                </buttton>
              </li>
              <li>
                <button
                type="button"
                id="deleteAllUsersButton"
                data-modal-target="deleteAllUsersModal"
                data-modal-toggle="deleteAllUsersModal"
                class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
              >
                <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    fill-rule="evenodd"
                    d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                    clip-rule="evenodd"
                  />
                </svg>
                Delete all
              </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          <tr>
            <th scope="col" class="p-4">
              <div class="flex items-center">
                <input
                  id="checkbox-all"
                  type="checkbox"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />
                <label for="checkbox-all" class="sr-only">checkbox</label>
              </div>
            </th>
            <th scope="col" class="px-4 py-3 font-semibold">Fecha</th>
            <th scope="col" class="px-4 py-3 font-semibold">Cliente</th>
            <th scope="col" class="px-4 py-3 font-semibold">Base</th>
            <th scope="col" class="whitespace-nowrap px-4 py-3 font-semibold">Total</th>
            <th scope="col" class="px-4 py-3 font-semibold">Acciones</th>
          </tr>
        </thead>
  
                    <tbody class="divide-y divide-default-100">
                    @forelse($this->facturas as $factura)
                        <tr class="hover:bg-default-50/40">
<td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                  <input
                  id="checkbox{{ $factura->codfactura ?? $factura->id }}"
                  type="checkbox"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                />  
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $factura->codfactura ?? $factura->id }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ optional($factura->fechaemitido)->format('d/m/Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ optional($factura->cliente)->nombretotal ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ number_format($factura->baseimponible, 2, ',', '.') }} €
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ number_format($factura->importe, 2, ',', '.') }} €
                            </td> 
                         <td class="pwhitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                     <button
                id="invoice-3-dropdown-button"
                type="button"
                data-dropdown-toggle="invoice-3-dropdown"
                class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              >
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-width="4" d="M6 12h0m6 0h0m6 0h0" />
                </svg>
              </button>
              <div id="invoice-3-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="invoice-3-dropdown-button">
                  <li>
                    <a                                     wire:click="editFactura({{ $factura->id }})"
  class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                     
                        <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M11.32 6.176H5c-1.105 0-2 .949-2 2.118v10.588C3 20.052 3.895 21 5 21h11c1.105 0 2-.948 2-2.118v-7.75l-3.914 4.144A2.46 2.46 0 0 1 12.81 16l-2.681.568c-1.75.37-3.292-1.263-2.942-3.115l.536-2.839c.097-.512.335-.983.684-1.352l2.914-3.086Z"
                          clip-rule="evenodd"
                        />
                        <path
                          fill-rule="evenodd"
                          d="M19.846 4.318a2.148 2.148 0 0 0-.437-.692 2.014 2.014 0 0 0-.654-.463 1.92 1.92 0 0 0-1.544 0 2.014 2.014 0 0 0-.654.463l-.546.578 2.852 3.02.546-.579a2.14 2.14 0 0 0 .437-.692 2.244 2.244 0 0 0 0-1.635ZM17.45 8.721 14.597 5.7 9.82 10.76a.54.54 0 0 0-.137.27l-.536 2.84c-.07.37.239.696.588.622l2.682-.567a.492.492 0 0 0 .255-.145l4.778-5.06Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      Edit
                    </a>
                  </li>
                  <a href="../invoice" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                    <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M4.998 7.78C6.729 6.345 9.198 5 12 5c2.802 0 5.27 1.345 7.002 2.78a12.713 12.713 0 0 1 2.096 2.183c.253.344.465.682.618.997.14.286.284.658.284 1.04s-.145.754-.284 1.04a6.6 6.6 0 0 1-.618.997 12.712 12.712 0 0 1-2.096 2.183C17.271 17.655 14.802 19 12 19c-2.802 0-5.27-1.345-7.002-2.78a12.712 12.712 0 0 1-2.096-2.183 6.6 6.6 0 0 1-.618-.997C2.144 12.754 2 12.382 2 12s.145-.754.284-1.04c.153-.315.365-.653.618-.997A12.714 12.714 0 0 1 4.998 7.78ZM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    View
                  </a>
                  <button
                    type="button"
                    id="archiveModalButton"
                    data-modal-target="archiveModal"
                    data-modal-toggle="archiveModal"
                    class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                  >
                    <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                      <path d="M2 6a2 2 0 0 1 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                    </svg>
                    Archive
                  </button>
                </ul>
                <div class="p-2">
                  <button
                    type="button"
                    id="deleteInvoiceButton"
                    data-modal-target="deleteInvoiceModal"
                    data-modal-toggle="deleteInvoiceModal"
                    class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
                            <button
                                    type="button"
                                    wire:click="editFactura({{ $factura->id }})"
                                    class="inline-flex items-center rounded-md border border-default-300 px-2 py-1 text-xs hover:border-primary hover:text-primary"
                                >
                                    Editar
                                </button>
                                 
         
                                <div id="user-1-dropdown" class="z-10 hidden w-40 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700">
                <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400" aria-labelledby="user-1-dropdown-button">
                  <li>
                    <button
                      type="button"
                      id="updateUserButton"
                      data-modal-target="updateUserModal"
                      data-modal-toggle="updateUserModal"
                      class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M11.3 6.2H5a2 2 0 0 0-2 2V19a2 2 0 0 0 2 2h11c1.1 0 2-1 2-2.1V11l-4 4.2c-.3.3-.7.6-1.2.7l-2.7.6c-1.7.3-3.3-1.3-3-3.1l.6-2.9c.1-.5.4-1 .7-1.3l3-3.1Z" clip-rule="evenodd" />
                        <path
                          fill-rule="evenodd"
                          d="M19.8 4.3a2.1 2.1 0 0 0-1-1.1 2 2 0 0 0-2.2.4l-.6.6 2.9 3 .5-.6a2.1 2.1 0 0 0 .6-1.5c0-.2 0-.5-.2-.8Zm-2.4 4.4-2.8-3-4.8 5-.1.3-.7 3c0 .3.3.7.6.6l2.7-.6.3-.1 4.7-5Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      Edit
                    </button>
                  </li>
                  <li>
                    <button
                      type="button"
                      id="viewUserButton"
                      data-modal-target="readUserModal"
                      data-modal-toggle="readUserModal"
                      class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    >
                      <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path
                          fill-rule="evenodd"
                          d="M5 7.8C6.7 6.3 9.2 5 12 5s5.3 1.3 7 2.8a12.7 12.7 0 0 1 2.7 3.2c.2.2.3.6.3 1s-.1.8-.3 1a2 2 0 0 1-.6 1 12.7 12.7 0 0 1-9.1 5c-2.8 0-5.3-1.3-7-2.8A12.7 12.7 0 0 1 2.3 13c-.2-.2-.3-.6-.3-1s.1-.8.3-1c.1-.4.3-.7.6-1 .5-.7 1.2-1.5 2.1-2.2Zm7 7.2a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"
                          clip-rule="evenodd"
                        />
                      </svg>
                      View
                    </button>
                  </li>
                  <li>
                    <button 
                    type="button" 
                    type="button" 
                    id="archiveUserButton"
                    data-modal-target="archiveUserModal"
                    data-modal-toggle="archiveUserModal"
                    class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
                      <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M20 10H4v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8ZM9 13v-1h6v1c0 .6-.4 1-1 1h-4a1 1 0 0 1-1-1Z" clip-rule="evenodd" />
                        <path d="M2 6c0-1.1.9-2 2-2h16a2 2 0 1 1 0 4H4a2 2 0 0 1-2-2Z" />
                      </svg>
                      Archive
                    </button>
                  </li>
                </ul>
                <div class="p-2">
                  <button
                    type="button"
                    id="deleteUserButton"
                    data-modal-target="deleteUserModal"
                    data-modal-toggle="deleteUserModal"
                    class="inline-flex w-full items-center rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600"
                  >
                    <svg class="me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    Delete
                  </button>
                </div>
              </div>
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
    </div>
    <nav class="flex flex-row items-center justify-between p-4" aria-label="Table navigation">
      <button
        type="button"
        class="flex items-center justify-center rounded-lg bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
      >
        Download CSV
      </button>
      <p class="text-sm">
        <span class="font-normal text-gray-500 dark:text-gray-400">Total users:</span>
        <span class="font-semibold text-gray-900 dark:text-white">1867</span>
      </p>
    </nav>
  </div>

        </x-ui.sidebar-inset>

    </x-ui.sidebar-provider>



<!-- Edit User Modal -->
<div id="updateUserModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden md:inset-0">
  <div class="relative max-h-full w-full max-w-2xl p-4">
    <!-- Modal content -->
    <form action="#" class="relative rounded-lg bg-white shadow-sm dark:bg-gray-800">
      <!-- Modal header -->
      <div class="flex items-center justify-between rounded-t px-4 py-4 sm:px-5">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Update user</h3>
        <button
          type="button"
          class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          data-modal-toggle="updateUserModal"
        >
        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6"/>
        </svg>
          <span class="sr-only">Close modal</span>
        </button>
      </div>
      <div id="accordion-collapse-update-user" data-accordion="collapse">
        <h2 id="accordion-collapse-heading-1">
          <button
            type="button"
            class="flex w-full items-center justify-between bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-1"
            aria-expanded="true"
            aria-controls="accordion-collapse-body-1"
          >
            <span>General Information</span>
            <svg data-accordion-icon="" class="h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-1" class="" aria-labelledby="accordion-collapse-heading-1">
          <div class="border-gray-200 p-4 dark:border-gray-700 sm:p-5">
            <!-- Inputs -->
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="file_input">Upload avatar</label>
                <div class="w-full items-center sm:flex">
                  <img class="mb-4 h-20 w-20 rounded-full sm:mb-0 sm:mr-4" src="/images/users/helene-engels.png" alt="Helene avatar" />
                  <div class="w-full">
                    <input
                      class="w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-gray-900 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:placeholder-gray-400"
                      aria-describedby="file_input_help"
                      id="file_input"
                      type="file"
                    />
                    <p class="mb-3 mt-1 text-xs font-normal text-gray-500 dark:text-gray-300" id="file_input_help">SVG, PNG, JPG or GIF (MAX. 800x400px).</p>
                    <div class="flex items-center space-x-2.5">
                      <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-primary-700 px-3 py-2 text-center text-xs font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                      >
                        <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                          <path
                            fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z"
                            clip-rule="evenodd"
                          />
                        </svg>
                        Upload new picture
                      </button>
                      <button
                        type="button"
                        class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                      >
                        Delete
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div>
                <label for="first-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">First Name</label>
                <input
                  type="text"
                  name="first-name"
                  id="first-name"
                  value="Bonnie"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="John"
                  required=""
                />
              </div>
              <div>
                <label for="last-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Last Name</label>
                <input
                  type="text"
                  name="last-name"
                  id="last-name"
                  value="Green"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Doe"
                  required=""
                />
              </div>
              <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email</label>
                <input
                  type="email"
                  name="email"
                  id="email"
                  value="bonnie.green@company.com"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="john@company.com"
                  required=""
                />
              </div>
              <div>
                <label for="user-permissions" class="mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Permissions
                  <button type="button" data-tooltip-target="tooltip-dark" data-tooltip-style="dark" class="ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">User permission details</span>
                  </button>
                  <div id="tooltip-dark" role="tooltip" class="tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    User permissions, part of the overall user management process, are access granted to users to specific resources such as files, applications, networks, or devices.
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-permissions"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Operational</option>
                  <option value="NO">Non Operational</option>
                </select>
              </div>
              <div>
                <label for="job-title" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Job Title</label>
                <input
                  type="text"
                  name="job-title"
                  id="job-title"
                  value="Back-end software engineer"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. React Developer"
                  required=""
                />
              </div>
              <div>
                <label for="languages" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Languages</label>
                <input
                  type="text"
                  name="languages"
                  id="languages"
                  value="English, German"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. English, Spanish"
                  required=""
                />
              </div>
              <div>
                <label for="account" class="mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Account
                  <button type="button" data-tooltip-target="tooltip-account" data-tooltip-style="dark" class="ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">Account details</span>
                  </button>
                  <div id="tooltip-account" role="tooltip" class="tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Choose here your account type.
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="account"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">PRO Account</option>
                  <option value="DF">Default Account</option>
                </select>
              </div>
              <div>
                <label for="user-role" class="mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  User Role
                  <button type="button" data-tooltip-target="tooltip-user-role" data-tooltip-style="dark" class="ml-1 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">User role details</span>
                  </button>
                  <div id="tooltip-user-role" role="tooltip" class="tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    Flowbite provides 7 predefined roles: Owner, Admin, Editor, Contributor and Viewer. Assign the most suitable role to each user, giving them the most appropriate level of control.
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="user-role"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Owner</option>
                  <option value="AD">Admin</option>
                  <option value="ED">Editor</option>
                  <option value="CO">Contributor</option>
                  <option value="VI">Viewer</option>
                </select>
              </div>
              <div class="sm:col-span-2">
                <label for="email-status" class="mb-2 inline-flex items-center text-sm font-medium text-gray-900 dark:text-white">
                  Email Status
                  <button type="button" data-tooltip-target="tooltip-email-status" data-tooltip-style="dark" class="ml-1 h-4 w-4 text-gray-400 hover:text-gray-900 dark:text-gray-500 dark:hover:text-white">
                    <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        fill-rule="evenodd"
                        d="M2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10S2 17.523 2 12Zm9.008-3.018a1.502 1.502 0 0 1 2.522 1.159v.024a1.44 1.44 0 0 1-1.493 1.418 1 1 0 0 0-1.037.999V14a1 1 0 1 0 2 0v-.539a3.44 3.44 0 0 0 2.529-3.256 3.502 3.502 0 0 0-7-.255 1 1 0 0 0 2 .076c.014-.398.187-.774.48-1.044Zm.982 7.026a1 1 0 1 0 0 2H12a1 1 0 1 0 0-2h-.01Z"
                        clip-rule="evenodd"
                      />
                    </svg>
                    <span class="sr-only">Email status details</span>
                  </button>
                  <div id="tooltip-email-status" role="tooltip" class="tooltip invisible absolute z-10 inline-block max-w-sm rounded-lg bg-gray-900 px-3 py-2 text-sm font-normal text-white opacity-0 shadow-xs dark:bg-gray-600">
                    As an administrator, you can view the status of a user's email. The status indicates whether a user's email is verified or not.
                    <div class="tooltip-arrow" data-popper-arrow></div>
                  </div>
                </label>
                <select
                  id="email-status"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">Verified</option>
                  <option value="NV">Not verified</option>
                </select>
              </div>
              <div>
                <label for="password" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Password</label>
                <input
                  type="password"
                  name="password"
                  id="password"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div>
                <label for="confirm-password" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Confirm password</label>
                <input
                  type="password"
                  name="confirm-password"
                  id="confirm-password"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="•••••••••"
                  required=""
                />
              </div>
              <div class="sm:col-span-2">
                <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white" for="role">Assign Role</label>
                <div class="flex space-x-4">
                  <div class="flex items-center">
                    <input
                      id="inline-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Administrator</label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="inline-2-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-2-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Member</label>
                  </div>
                  <div class="flex items-center">
                    <input
                      checked=""
                      id="inline-checked-checkbox"
                      type="checkbox"
                      value=""
                      name="role"
                      class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                    />
                    <label for="inline-checked-checkbox" class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Viewer</label>
                  </div>
                </div>
              </div>
              <div>
                <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</div>
                <label for="status" class="relative inline-flex cursor-pointer items-center">
                  <input type="checkbox" value="" id="status" class="peer sr-only" />
                  <div
                    class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800"
                  ></div>
                  <span class="ml-3 text-sm font-medium text-gray-500 dark:text-gray-300">Inactive</span>
                </label>
              </div>
            </div>
          </div>
        </div>
        <h2 id="accordion-collapse-heading-2">
          <button
            type="button"
            class="flex w-full items-center justify-between border-t border-gray-200 bg-gray-50 px-4 py-4 text-left font-medium leading-none text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 dark:border-gray-700 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-800 sm:px-5"
            data-accordion-target="#accordion-collapse-body-2"
            aria-expanded="false"
            aria-controls="accordion-collapse-body-2"
          >
            <span>Additional Information</span>
            <svg data-accordion-icon="" class="h-5 w-5 shrink-0 rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 15 7-7 7 7" />
            </svg>
          </button>
        </h2>
        <div id="accordion-collapse-body-2" class="hidden" aria-labelledby="accordion-collapse-heading-2">
          <div class="border-gray-200 px-4 pt-4 dark:border-gray-700 sm:px-5 sm:pt-5">
            <!-- Inputs -->
            <div class="grid gap-4 sm:grid-cols-2">
              <div class="col-span-2">
                <label for="skills" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Skills</label>
                <input
                  type="text"
                  name="skills"
                  id="skills"
                  value="Tailwind CSS, Flowbite, React"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. Figma, HTML, Javascript"
                  required=""
                />
              </div>
              <div class="col-span-2">
                <label for="phone-number" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Phone Number</label>
                <input
                  type="text"
                  name="phone-number"
                  id="phone-number"
                  value="+1631 442 978"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. +1234 567 890"
                  required=""
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="linkedin" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Linkedin URL</label>
                <input
                  type="url"
                  name="linkedin"
                  id="linkedin"
                  value="https://www.linkedin.com/in/bonniegreen/"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://www.linkedin.com/in/example/"
                  required=""
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="facebook" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Facebook</label>
                <input
                  type="url"
                  name="facebook"
                  id="facebook"
                  value="https://www.facebook.com/bonniegreen"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://www.facebook.com/example"
                  required=""
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="twitter" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Twitter</label>
                <input
                  type="url"
                  name="twitter"
                  id="twitter"
                  value="https://twitter.com/bonniegreen"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://twitter.com/example"
                  required=""
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="personal-website" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Personal Website</label>
                <input
                  type="url"
                  name="personal-website"
                  id="personal-website"
                  value="https://flowbite.com"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. https://website.com"
                  required=""
                />
              </div>
              <div class="col-span-2">
                <label for="country" class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Country</label>
                <select
                  id="country"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                >
                  <option selected="">United States</option>
                  <option value="NO">Australia</option>
                  <option value="NO">United Kingdom</option>
                  <option value="NO">Italy</option>
                  <option value="NO">Germany</option>
                  <option value="NO">Spain</option>
                  <option value="NO">France</option>
                  <option value="NO">Canada</option>
                </select>
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="address" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Address</label>
                <input
                  type="text"
                  name="address"
                  id="address"
                  value="92 Milles Drive, Newark, NJ 07123"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. 92 Milles Drive, Newark, NJ 07123"
                  required=""
                />
              </div>
              <div class="col-span-2 sm:col-span-1">
                <label for="timezone" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Timezone</label>
                <input
                  type="text"
                  name="timezone"
                  id="timezone"
                  value="GMT+3"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Ex. GMT+2"
                  required=""
                />
              </div>
              <div class="col-span-2">
                <label for="biography" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Biography</label>
                <textarea
                  id="biography"
                  rows="4"
                  class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 placeholder:text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
                  placeholder="Write your biography..."
                >
Hello, I'm Helene Engels, USA Designer, Creating things that stand out, Featured by Adobe, Figma, Webflow and others, Daily design tips & resources, Exploring Web3.</textarea
                >
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="flex items-center space-x-4 px-4 py-4 sm:px-5 sm:py-5">
        <button
          type="submit"
          class="rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          Update user
        </button>
        <button
          type="button"
          class="focus:outline-non inline-flex items-center rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900"
        >
          <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path
              fill-rule="evenodd"
              d="M8.6 2.6A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4c0-.5.2-1 .6-1.4ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z"
              clip-rule="evenodd"
            />
          </svg>
          Delete
        </button>
      </div>
    </form>
  </div>
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
                                <option value="{{ $empresa['id'] }}">{{ $empresa['empresa'] }}</option>
                            @endforeach
                        </select>
                        @error('form.empresa_id') <p class="text-xs font-medium text-rose-500 mt-0.5">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">

                        <label class="text-xs font-semibold text-slate-600 dark:text-slate-400">Cliente</label>


                        <select
                            wire:model.live="filters.cliente"
                            class="w-full rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 px-3 py-2.5 text-sm text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all"
                        >
                            <option value="">Cliente...</option>
                            @foreach($this->clientes as $cliente)
                                <option value="{{ $cliente['codcliente'] }}">{{ $cliente['nombretotal'] }}</option>
                            @endforeach
                        </select>
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
                <div class="flex bg-gradient-to-br from-slate-900 to-indigo-950 p-6 rounded-2xl text-white shadow-lg space-y-4">
                    <div class="space-y-2 text-xs">
                        <div class="col-1 grid justify-between items-center text-slate-300 ">
                            <span>Base imponible</span>
                            <span class="font-semibold text-white">
                                {{ number_format($form['baseimponible'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="col-2 grid justify-between items-center text-slate-300 ">
                            <span>Base exenta</span>
                            <span class="font-semibold text-white">
                                {{ number_format($form['baseexenta'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="col-3 grid justify-between items-center text-slate-300 ">
                            <span>IGIC (7%)</span>
                            <span class="font-semibold text-emerald-400">
                                +{{ number_format($form['impuesto'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="col-4 grid justify-between items-center text-slate-300 ">
                            <span>Retenciones (15%)</span>
                            <span class="font-semibold text-rose-400">
                                -{{ number_format($form['retenciones'], 2, ',', '.') }} €
                            </span>
                        </div>
                        <div class="w-full border-t border-white/10 my-2 pt-2.5 flex justify-between items-center text-sm">
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
                        class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-900/40 px-5 py-2.5 text-xs font-bold text-white shadow-md shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer"
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



