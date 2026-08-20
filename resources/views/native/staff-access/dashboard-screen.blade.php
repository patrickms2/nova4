<scroll-view class="w-full h-full bg-surface">
    <column class="w-full px-6 py-8 gap-6">
        <row class="w-full items-center justify-between">
            <text class="text-2xl font-extrabold text-on-surface">NOVA Access</text>
            <pressable @press="logout" class="px-3 py-1 bg-error rounded-lg items-center justify-center">
                <text class="text-sm font-medium text-on-error">Salir</text>
            </pressable>
        </row>

        @if ($error)
            <column class="p-4 bg-error-container rounded-xl">
                <text class="text-sm text-on-error-container text-center">{{ $error }}</text>
            </column>
        @endif

        @if ($loading)
            <column class="items-center justify-center py-8">
                <text class="text-on-surface-variant">Cargando…</text>
            </column>
        @endif

        @if ($session === null)
            <column class="gap-4">
                <text class="text-lg font-semibold text-on-surface">Selecciona acceso</text>

                @if (count($grants) === 0)
                    <text class="text-on-surface-variant">No tienes permisos activos.</text>
                @else
                    <picker wire:model="selectedGrantId" class="w-full">
                        @foreach ($grants as $grant)
                            <picker-item value="{{ $grant['id'] }}" label="{{ $grant['name'] }}" />
                        @endforeach
                    </picker>

                    @php
                        $points = collect($grants)->firstWhere('id', $selectedGrantId)['access_points'] ?? [];
                    @endphp

                    @if (count($points) > 0)
                        <picker wire:model="selectedPointId" class="w-full">
                            @foreach ($points as $point)
                                <picker-item value="{{ $point['id'] }}" label="{{ $point['name'] }}" />
                            @endforeach
                        </picker>
                    @endif

                    <pressable @press="startSession" class="w-full py-4 bg-primary rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-primary">ENTRAR</text>
                    </pressable>
                @endif
            </column>
        @else
            @php
                $status = $session['status'];
                $isWorking = $status === 'active';
                $needsReport = $status === 'report_pending';
                $canComplete = in_array($status, ['finishing', 'exit_authorized']);
                $isFinished = $status === 'finished';
            @endphp

            <column class="gap-4 p-6 bg-surface-variant rounded-2xl items-center">
                <text class="text-sm uppercase tracking-wide text-on-surface-variant">Estado</text>
                <text class="text-3xl font-extrabold text-on-surface">{{ $session['status_label'] }}</text>

                @if ($isWorking)
                    <text class="text-base text-on-surface-variant">{{ gmdate('H:i:s', $session['elapsed_seconds']) }}</text>

                    <pressable @press="finishSession" class="w-full py-4 bg-warning rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-warning">TERMINAR</text>
                    </pressable>
                @endif

                @if ($needsReport)
                    <text class="text-center text-on-surface-variant">Se requiere parte de trabajo.</text>
                    <pressable @navigate="'/staff/report/' . $session['id']" class="w-full py-4 bg-primary rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-primary">RELLENAR PARTE</text>
                    </pressable>
                @endif

                @if ($canComplete)
                    <pressable @press="completeSession" class="w-full py-4 bg-primary rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-primary">ABRIR SALIDA</text>
                    </pressable>
                @endif

                @if ($isFinished)
                    <text class="text-center text-success">Sesión completada correctamente.</text>
                    <pressable @press="loadState" class="w-full py-3 bg-surface rounded-xl border border-outline items-center justify-center">
                        <text class="text-base font-medium text-on-surface">Nueva entrada</text>
                    </pressable>
                @endif
            </column>
        @endif
    </column>
</scroll-view>
