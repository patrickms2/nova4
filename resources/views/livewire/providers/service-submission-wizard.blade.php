<div
    class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#fff7ed,_transparent_28%),radial-gradient(circle_at_top_right,_#ecfeff,_transparent_34%),#f4f7fb]">
    <div class="mx-auto max-w-md px-5 py-6">
        <header class="mb-6 flex items-center justify-between">
            <div>
                <div class="text-2xl font-black tracking-tight text-blue-600">TAXILANZ</div>
                <div class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-400">Alta 1-click</div>
            </div>

            @if($step > 1 && $step < 5)
                <button
                    wire:click="back"
                    class="rounded-full border border-white/40 bg-white/70 px-4 py-2 text-sm font-medium text-slate-700 backdrop-blur-xl shadow-sm"
                >
                    ← Volver
                </button>
            @endif
        </header>

        {{-- STEP 1 --}}
        @if($step === 1)
            <section
                x-data
                x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
                class="opacity-0 translate-y-6 scale-[0.985] blur-[2px] transition-all duration-[620ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:scale-100 [&.is-in]:opacity-100 [&.is-in]:blur-0"
            >
                <div
                    class="rounded-[32px] border border-white/40 bg-white/80 p-5 backdrop-blur-2xl shadow-[0_24px_60px_rgba(15,23,42,0.10)]">
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Paso 1</div>
                    <h1 class="mt-2 text-4xl font-black leading-none text-slate-950">
                        ¿Qué quieres ofrecer?
                    </h1>
                    <p class="mt-3 text-lg leading-7 text-slate-500">
                        Elige el tipo y yo te ayudo a transformarlo en una propuesta lista para TAXILANZ.
                    </p>

                    <div class="mt-6 grid grid-cols-2 gap-3">
                        @foreach($types as $key => $label)
                            <button
                                wire:click="selectType('{{ $key }}')"
                                class="rounded-[24px] border border-white/50 bg-white/70 px-4 py-4 text-left text-sm font-semibold text-slate-800 backdrop-blur-md transition duration-200 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:scale-[1.02] hover:bg-white active:scale-[0.98]"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- STEP 2 --}}
        @if($step === 2)
            <section
                x-data
                x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
                class="opacity-0 translate-y-6 transition-all duration-[520ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
            >
                <div
                    class="rounded-[32px] border border-white/40 bg-white/80 p-5 backdrop-blur-2xl shadow-[0_24px_60px_rgba(15,23,42,0.10)]">
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Paso 2</div>
                    <h2 class="mt-2 text-3xl font-black leading-tight text-slate-950">
                        Cuéntamelo en una frase
                    </h2>
                    <p class="mt-3 text-slate-500">
                        Habla como lo harías con un cliente. Yo me encargo de estructurarlo.
                    </p>

                    <textarea
                        wire:model="rawInputText"
                        rows="6"
                        class="mt-5 w-full rounded-[24px] border border-white/40 bg-slate-100/70 px-4 py-4 text-slate-900 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        placeholder="Ej. Hacemos sombreros artesanales con materiales locales y nos gusta contar la historia de cada pieza..."
                    ></textarea>

                    <!-- Botón de voz -->
                    <div class="mt-4 flex justify-center"
                         x-data="{
                            isRecording: false,
                            recorder: null,
                            stream: null,
                            chunks: [],
                            status: '',

                            async start() {
                                try {
                                    this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                                    this.recorder = new MediaRecorder(this.stream);
                                    this.chunks = [];
                                    this.recorder.ondataavailable = e => this.chunks.push(e.data);
                                    this.recorder.onstop = async () => {
                                        const blob = new Blob(this.chunks, { type: 'audio/webm' });
                                        await this.send(blob);
                                    };
                                    this.recorder.start();
                                    this.isRecording = true;
                                    this.status = 'Grabando...';
                                } catch (e) { this.status = 'Error micro'; }
                            },

                            stop() {
                                if (this.recorder) {
                                    this.recorder.stop();
                                    this.isRecording = false;
                                    this.stream.getTracks().forEach(t => t.stop());
                                }
                            },

                            async send(blob) {
                                this.status = 'Transcribiendo...';
                                $wire.set('isTranscribing', true);
                                const fd = new FormData();
                                fd.append('audio', blob, 'v.webm');
                                try {
                                    const r = await fetch('/transcribe', {
                                        method: 'POST',
                                        body: fd,
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                    });
                                    const d = await r.json();
                                    if (d.ok) {
                                        $wire.dispatch('voiceTranscribed', { data: d });
                                        this.status = '¡Listo!';
                                    } else { this.status = 'Error'; $wire.set('isTranscribing', false); }
                                } catch (e) { this.status = 'Error red'; $wire.set('isTranscribing', false); }
                            }
                         }"
                    >
                        <button
                            @click="isRecording ? stop() : start()"
                            type="button"
                            class="flex items-center gap-2 rounded-full px-6 py-3 font-bold transition-all"
                            :class="isRecording ? 'bg-rose-100 text-rose-600 animate-pulse' : 'bg-blue-50 text-blue-600 hover:bg-blue-100'"
                        >
                            <span x-show="!isRecording">🎙️ Grabar descripción</span>
                            <span x-show="isRecording">⏹️ Detener ( <span x-text="status"></span> )</span>
                        </button>
                    </div>

                    @error('rawInputText')
                    <p class="mt-3 text-sm text-rose-500">{{ $message }}</p>
                    @enderror

                    <button
                        wire:click="processInput"
                        class="mt-5 w-full rounded-2xl bg-blue-600 px-5 py-4 text-base font-semibold text-white shadow-[0_16px_32px_rgba(37,99,235,0.28)] transition duration-200 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:scale-[1.01] active:scale-[0.985]"
                    >
                        {{ $processing ? 'Procesando...' : 'Siguiente' }}
                    </button>
                </div>
            </section>
        @endif

        {{-- STEP 3 --}}
        @if($step === 3)
            <section
                x-data
                x-init="
                    setTimeout(() => $refs.title.classList.add('is-in'), 40);
                    setTimeout(() => $refs.card.classList.add('is-in'), 180);
                    setTimeout(() => $refs.cta.classList.add('is-in'), 320);
                "
            >
                <div
                    x-ref="title"
                    class="opacity-0 translate-y-4 transition-all duration-[420ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
                >
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Paso 3</div>
                    <h2 class="mt-2 text-3xl font-black leading-tight text-slate-950">
                        Así podría verse tu propuesta
                    </h2>
                    <p class="mt-3 text-slate-500">
                        He preparado una versión clara, atractiva y lista para revisión.
                    </p>
                </div>

                <div
                    x-ref="card"
                    class="mt-5 rounded-[32px] border border-white/40 bg-white/80 p-5 backdrop-blur-2xl shadow-[0_24px_60px_rgba(15,23,42,0.10)] opacity-0 translate-y-4 transition-all duration-[520ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
                >
                    <div class="rounded-[24px] bg-slate-100/80 p-4">
                        <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">Vista previa</div>
                        <h3 class="mt-2 text-2xl font-black text-slate-950">{{ $suggestedTitle }}</h3>
                        <p class="mt-2 text-slate-500">{{ $suggestedExcerpt }}</p>

                        <div class="mt-4 flex flex-wrap gap-2 text-sm font-semibold text-slate-600">
                            <span
                                class="rounded-full bg-white px-3 py-2">💶 €{{ number_format($suggestedPriceFrom ?? 0, 0) }}</span>
                            <span
                                class="rounded-full bg-white px-3 py-2">⏱ {{ $suggestedDurationMinutes ?? 30 }} min</span>
                            <span
                                class="rounded-full bg-emerald-50 px-3 py-2 text-emerald-700">Listo para revisión</span>
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/50 bg-white/70 p-4 backdrop-blur-md">
                        <div class="text-sm font-semibold text-slate-700">Resumen generado</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $suggestedDescription }}
                        </p>
                    </div>
                </div>

                <div
                    x-ref="cta"
                    class="mt-5 opacity-0 translate-y-4 transition-all duration-[520ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
                >
                    <button
                        wire:click="acceptSuggestion"
                        class="w-full rounded-2xl bg-slate-950 px-5 py-4 text-base font-semibold text-white shadow-[0_16px_32px_rgba(15,23,42,0.24)] transition duration-200 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:scale-[1.01] active:scale-[0.985]"
                    >
                        Está bien, continuar
                    </button>
                </div>
            </section>
        @endif

        {{-- STEP 4 --}}
        @if($step === 4)
            <section
                x-data
                x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
                class="opacity-0 translate-y-6 transition-all duration-[520ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
            >
                <div
                    class="rounded-[32px] border border-white/40 bg-white/80 p-5 backdrop-blur-2xl shadow-[0_24px_60px_rgba(15,23,42,0.10)]">
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Paso 4</div>
                    <h2 class="mt-2 text-3xl font-black leading-tight text-slate-950">
                        Añade una imagen
                    </h2>
                    <p class="mt-3 text-slate-500">
                        Opcional, pero ayuda mucho a enamorar.
                    </p>

                    <div
                        class="mt-5 rounded-[24px] border border-dashed border-white/60 bg-slate-100/70 p-5 text-center relative"
                        wire:key="upload-container-{{ $step }}">
                        @if($image && method_exists($image, 'temporaryUrl'))
                            <img src="{{ $image->temporaryUrl() }}"
                                 class="mx-auto h-32 object-cover rounded-xl mb-3 shadow-md">
                        @else
                            <input type="file" wire:model.live="image" class="block w-full text-sm text-slate-500">
                        @endif
                        <p class="mt-3 text-sm text-slate-500">PNG, JPG o WEBP</p>
                    </div>

                    <button
                        wire:click="submitProposal"
                        class="mt-5 w-full rounded-2xl bg-blue-600 px-5 py-4 text-base font-semibold text-white shadow-[0_16px_32px_rgba(37,99,235,0.28)] transition duration-200 ease-[cubic-bezier(0.2,0.8,0.2,1)] hover:scale-[1.01] active:scale-[0.985]"
                    >
                        Enviar para validación
                    </button>
                </div>
            </section>
        @endif

        {{-- STEP 5 --}}
        @if($step === 5)
            <section
                x-data
                x-init="
                    setTimeout(() => $refs.check.classList.add('is-in'), 40);
                    setTimeout(() => $refs.box.classList.add('is-in'), 160);
                "
                class="text-center"
            >
                <div
                    x-ref="check"
                    class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-emerald-50 text-5xl opacity-0 scale-75 transition-all duration-[520ms] ease-[cubic-bezier(0.2,0.8,0.2,1)] [&.is-in]:opacity-100 [&.is-in]:scale-100"
                >
                    ✨
                </div>

                <div
                    x-ref="box"
                    class="mt-6 rounded-[32px] border border-white/40 bg-white/80 p-6 backdrop-blur-2xl shadow-[0_24px_60px_rgba(15,23,42,0.10)] opacity-0 translate-y-4 transition-all duration-[520ms] ease-[cubic-bezier(0.22,1,0.36,1)] [&.is-in]:translate-y-0 [&.is-in]:opacity-100"
                >
                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">Propuesta enviada
                    </div>
                    <h2 class="mt-2 text-3xl font-black leading-tight text-slate-950">
                        Ya formas parte del proceso
                    </h2>
                    <p class="mt-3 text-slate-500">
                        Revisaremos tu propuesta para asegurar calidad, autenticidad y buen encaje con el ecosistema.
                    </p>

                    <div class="mt-5 rounded-2xl bg-slate-100/80 p-4 text-left">
                        <div class="text-sm font-semibold text-slate-700">Siguiente paso</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Si todo encaja, tu propuesta podrá convertirse en una oferta visible dentro de TAXILANZ.
                        </p>
                    </div>
                </div>
            </section>
        @endif
    </div>
</div>
