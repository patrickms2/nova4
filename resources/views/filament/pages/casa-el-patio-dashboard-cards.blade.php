<x-filament-panels::page class="casa-el-patio-dashboard">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

        {{-- Reservas --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Reservas</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-600 dark:bg-primary-900 dark:text-primary-300">
                    {{ $sectionTotals['reservations'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Facturación mes</div>
                    <div class="text-xl font-semibold tracking-tight">€{{ number_format($kpis['billing'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Ocupación</div>
                    <div class="text-xl font-semibold tracking-tight">{{ $kpis['occupancy'] }}%</div>
                </div>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestReservations as $r)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm">
                            <div class="font-medium">{{ $r['guest'] }}</div>
                            <div class="text-xs text-gray-500">{{ $r['check_in'] }} - {{ $r['check_out'] }}</div>
                        </div>
                        <div class="text-sm font-semibold">€{{ number_format($r['amount'], 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin reservas recientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalReservationResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Ver reservas
                </a>
                <a href="{{ \App\Filament\App\Rentals\Pages\RentalContractSimulator::getUrl() }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Simulador
                </a>
                <a href="{{ \App\Filament\App\Rentals\Pages\RentalCalendarDashboard::getUrl() }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Calendario
                </a>
            </div>
        </x-filament::card>

        {{-- Finanzas --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Finanzas</h3>
            </div>

            <div class="mt-4 space-y-3 flex-1">
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Neto propietario</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['netOwner'], 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Cash flow gestor</span>
                    <span class="font-semibold text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['cashFlow'], 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Comisiones totales</span>
                    <span class="font-semibold text-rose-600 dark:text-rose-400">€{{ number_format($kpis['totalCommissions'], 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Servicios</span>
                    <span class="font-semibold text-amber-600 dark:text-amber-400">€{{ number_format($kpis['services'], 2, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Gastos operativos</span>
                    <span class="font-semibold text-rose-600 dark:text-rose-400">€{{ number_format($kpis['operatingExpenses'], 2, ',', '.') }}</span>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Pages\RentalContractSimulator::getUrl() }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Simulador contrato
                </a>
                <a href="{{ \App\Filament\App\Rentals\Pages\RentalOccupancyCalendar::getUrl() }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Calendario ocupación
                </a>
            </div>
        </x-filament::card>

        {{-- Gastos --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Gastos</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-600 dark:bg-rose-900 dark:text-rose-300">
                    {{ $sectionTotals['expenses'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Gastos mes</div>
                    <div class="text-xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($kpis['operatingExpenses'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pendientes</div>
                    <div class="text-xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($kpis['pendingExpenses'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestExpenses as $e)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm overflow-hidden">
                            <div class="font-medium truncate">{{ $e['description'] }}</div>
                            <div class="text-xs text-gray-500">{{ $e['expense_date'] }}</div>
                        </div>
                        <div class="text-sm font-semibold whitespace-nowrap">€{{ number_format($e['total_amount'], 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin gastos recientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalExpenseResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Ver gastos
                </a>
            </div>
        </x-filament::card>

        {{-- Inventario --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Inventario</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                    {{ $sectionTotals['inventory'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Valor</div>
                    <div class="text-xl font-semibold tracking-tight text-blue-600 dark:text-blue-400">€{{ number_format($kpis['inventoryValue'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Incidencias</div>
                    <div class="text-xl font-semibold tracking-tight">{{ $kpis['openIncidents'] }}</div>
                </div>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestInventory as $i)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm overflow-hidden">
                            <div class="font-medium truncate">{{ $i['category'] }} · {{ $i['location'] }}</div>
                            <div class="text-xs text-gray-500">{{ $i['status'] }}</div>
                        </div>
                        <div class="text-sm font-semibold whitespace-nowrap">€{{ number_format($i['purchase_value'], 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin bienes recientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalInventoryItemResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Ver inventario
                </a>
            </div>
        </x-filament::card>

        {{-- Documentos --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Documentos</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-600 dark:bg-amber-900 dark:text-amber-300">
                    {{ $sectionTotals['documents'] }}
                </span>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestDocuments as $d)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm overflow-hidden">
                            <div class="font-medium truncate">{{ $d['title'] }}</div>
                            <div class="text-xs text-gray-500">{{ $d['category'] }}{{ $d['expiry_date'] ? ' · ' . $d['expiry_date'] : '' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin documentos recientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalDocumentResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Ver documentos
                </a>
            </div>
        </x-filament::card>

        {{-- Tareas --}}
        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Tareas</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-600 dark:bg-purple-900 dark:text-purple-300">
                    {{ $sectionTotals['tasks'] }}
                </span>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestTasks as $t)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm overflow-hidden">
                            <div class="font-medium truncate">{{ $t['title'] }}</div>
                            <div class="text-xs text-gray-500">{{ $t['due_date'] ? $t['due_date'] : 'Sin fecha' }}</div>
                        </div>
                        <div class="text-xs font-semibold uppercase whitespace-nowrap">{{ $t['status'] }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin tareas pendientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalTaskResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Ver tareas
                </a>
            </div>
        </x-filament::card>
            </div>

        {{-- Acceso y Domótica --}}
    <div class="grid grid-cols-1 gap-4">

        <x-filament::card class="h-full flex flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Acceso y Domótica</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $accessOperations['operational'] ? 'bg-success-50 text-success-700' : 'bg-danger-50 text-danger-700' }}">
                    {{ $accessOperations['operational'] ? 'Operativa' : 'Requiere atención' }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="font-semibold">{{ $accessOperations['online'] }} online / {{ $accessOperations['offline'] }} offline</div>
                    <div class="text-xs text-gray-500">Dispositivos</div>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="font-semibold">{{ $domoticTotals['accessPoints'] }}</div>
                    <div class="text-xs text-gray-500">Puntos de acceso</div>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="font-semibold">{{ $accessOperations['activeGrants'] }}</div>
                    <div class="text-xs text-gray-500">Permisos activos</div>
                </div>
                <div class="p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                    <div class="font-semibold">{{ $domoticTotals['automations'] }}</div>
                    <div class="text-xs text-gray-500">Automatizaciones</div>
                </div>
            </div>

            <div class="mt-4 space-y-2 text-sm">
                @foreach ($accessOperations['points'] as $point)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <span>{{ $point['name'] }}</span>
                        <span class="text-xs {{ $point['online'] && $point['active'] ? 'text-success-600' : 'text-danger-600' }}">{{ $point['online'] ? 'Online' : 'Offline' }} · {{ $point['active'] ? 'Activo' : 'Inactivo' }}</span>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div class="text-xs font-semibold uppercase text-gray-500">Huésped actual</div>
                    <div class="mt-1 font-medium">{{ $accessOperations['currentGuest'] ?? 'Sin estancia activa' }}</div>
                    @if ($accessOperations['currentUntil'])<div class="text-xs text-gray-500">Hasta {{ $accessOperations['currentUntil'] }}</div>@endif
                </div>
                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                    <div class="text-xs font-semibold uppercase text-gray-500">Próxima llegada</div>
                    <div class="mt-1 font-medium">{{ $accessOperations['nextGuest'] ?? 'Sin llegada prevista' }}</div>
                    @if ($accessOperations['nextArrival'])<div class="text-xs text-gray-500">{{ $accessOperations['nextArrival'] }}</div>@endif
                    @if ($accessOperations['nextGuest'])
                        <div class="mt-2 text-xs space-y-1">
                            <div>{{ $accessOperations['personLinked'] ? '✓' : '○' }} Persona vinculada</div>
                            <div>{{ $accessOperations['grantCreated'] ? '✓' : '○' }} Permiso creado</div>
                            <div>{{ $accessOperations['credentialReady'] ? '✓' : '○' }} Credencial preparada</div>
                            <div>{{ $accessOperations['pointsAssigned'] ? '✓' : '○' }} Puntos asignados</div>
                            <div>○ Aprovisionamiento pendiente</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 space-y-2 flex-1">
                @forelse ($latestDomoticEvents as $e)
                    <div class="flex items-center justify-between p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="text-sm overflow-hidden">
                            <div class="font-medium truncate">{{ $e['title'] }}</div>
                            <div class="text-xs text-gray-500">{{ $e['type'] }} · {{ $e['time'] }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin eventos recientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ \App\Filament\App\Rentals\Domotics\Resources\Devices\DeviceResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-500 transition">
                    Dispositivos
                </a>
                <a href="{{ \App\Filament\App\Rentals\Domotics\Resources\AccessPoints\AccessPointResource::getUrl('index') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 transition">
                    Accesos
                </a>
                <a href="{{ \App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource::getUrl('create') }}" class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700 transition">
                    Crear permiso
                </a>
            </div>
        </x-filament::card>

    </div>
</x-filament-panels::page>
