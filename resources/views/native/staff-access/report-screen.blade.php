
<scroll-view class="w-full h-full bg-surface">
    <column class="w-full px-6 py-8 gap-6">
        <text class="text-2xl font-extrabold text-on-surface">Parte de trabajo</text>

        @if ($error)
            <column class="p-4 bg-error-container rounded-xl">
                <text class="text-sm text-on-error-container text-center">{{ $error }}</text>
            </column>
        @endif

        @if ($loading)
            <column class="items-center justify-center py-8">
                <text class="text-on-surface-variant">Enviando…</text>
            </column>
        @endif

        <column class="gap-4">
            @if ($voicePath === null)
                @if ($recording)
                    <pressable @press="stopRecording" class="w-full py-4 bg-error rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-error">🎙 Detener</text>
                    </pressable>
                @else
                    <pressable @press="startRecording" class="w-full py-4 bg-primary rounded-xl items-center justify-center">
                        <text class="text-lg font-bold text-on-primary">🎙 Grabar nota de voz</text>
                    </pressable>
                @endif
            @else
                <column class="p-4 bg-success-container rounded-xl">
                    <text class="text-on-success-container">Nota de voz grabada</text>
                    <pressable @press="clearVoice" class="mt-2">
                        <text class="text-sm text-on-success-variant">Eliminar</text>
                    </pressable>
                </column>
            @endif

            <pressable @press="takePhoto" class="w-full py-4 bg-secondary rounded-xl items-center justify-center">
                <text class="text-lg font-bold text-on-secondary">📷 Añadir foto</text>
            </pressable>

            @if (count($photos) > 0)
                <column class="gap-2">
                    <text class="text-sm text-on-surface-variant">Fotos: {{ count($photos) }}</text>
                    @foreach ($photos as $index => $photo)
                        <row class="items-center justify-between p-3 bg-surface-variant rounded-xl">
                            <text class="text-on-surface">Foto {{ $index + 1 }}</text>
                            <pressable @press="removePhoto({{ $index }})" class="px-3 py-1 bg-error rounded-lg">
                                <text class="text-sm text-on-error">Eliminar</text>
                            </pressable>
                        </row>
                    @endforeach
                </column>
            @endif

            <pressable @press="submitReport" class="w-full py-4 bg-primary rounded-xl items-center justify-center">
                <text class="text-lg font-bold text-on-primary">Enviar parte</text>
            </pressable>

            <pressable @navigate.back class="w-full py-3 bg-surface rounded-xl border border-outline items-center justify-center">
                <text class="text-base font-medium text-on-surface">Volver</text>
            </pressable>
        </column>
    </column>
</scroll-view>
