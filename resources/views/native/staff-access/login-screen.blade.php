<column class="w-full h-full px-6 py-8 gap-6 bg-surface">
    <column class="gap-2">
        <text class="text-3xl font-extrabold text-on-surface">NOVA Access</text>
        <text class="text-base text-on-surface-variant">Staff</text>
    </column>

    <column class="gap-4">
        @if ($error)
            <text class="text-sm text-error text-center">{{ $error }}</text>
        @endif

        <text-input native:model="email" placeholder="email@example.com" keyboard="email" class="w-full" />

        <text-input native:model="password" placeholder="Contraseña" secure class="w-full" />

        <pressable @press="login" class="w-full py-3 bg-primary rounded-xl items-center justify-center">
            <text class="text-base font-semibold text-on-primary">Entrar</text>
        </pressable>
    </column>
</column>
