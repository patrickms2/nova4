<div class="px-6 py-2 min-h-screen bg-slate-50">
    <div class="max-w-md mx-auto space-y-2">
        @if($step < 6)
            <div class="space-y-2">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Publica tu negocio en TAXILANZ</h1>
                <p class="text-slate-500 font-medium">Publica tu servicio en el ecosistema TAXILANZ con ayuda de nuestra
                    IA.</p>
                <p>• Sin formularios largos.</p>
                <p>• Con ayuda de IA.</p>
                <p>• Revisado antes de publicarse.</p>
            </div>

            <!-- Progress bar -->
            <div class="flex gap-2">
                @for($i = 1; $i <= 5; $i++)
                    <div
                        class="h-1.5 flex-1 rounded-full {{ $step >= $i ? 'bg-blue-600' : 'bg-slate-200' }} transition-colors"></div>
                @endfor
            </div>
        @endif

        @if($step === 1)
            <!-- STEP 1: Type -->
            <div class="space-y-4">
                <h2 class="text-lg font-bold text-slate-800">¿Qué ofreces?</h2>
                <div class="grid grid-cols-2 gap-4">
                    @foreach(['restaurant' => '🍴 Restaurante', 'experience' => '🌟 Experiencia', 'activity' => '🏄‍♂️ Actividad', 'product' => '🛍️ Producto', 'service' => '🛠️ Servicio'] as $key => $label)
                        <button wire:click="selectType('{{ $key }}')"
                                class="p-6 bg-white rounded-3xl border border-slate-100 shadow-sm hover:border-blue-500 hover:shadow-md transition-all text-left space-y-2 group active:scale-95">
                            <span class="text-2xl">{{ explode(' ', $label)[0] }}</span>
                            <span
                                class="block font-bold text-slate-700 group-hover:text-blue-600">{{ explode(' ', $label)[1] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

        @elseif($step === 2)
            <!-- STEP 2: Raw Input -->
            <div class="space-y-6">
                <h2 class="text-lg font-bold text-slate-800">Cuéntanoslo</h2>
                <div class="bg-white rounded-[32px] p-6 shadow-sm border border-slate-100 space-y-4"
                     x-data="{
                        recorder: null,
                        stream: null,
                        chunks: [],
                        isRecording: false,
                        status: '',

                        async startRecording() {
                            try {
                                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                                this.recorder = new MediaRecorder(this.stream);
                                this.chunks = [];
                                this.recorder.ondataavailable = (e) => this.chunks.push(e.data);
                                this.recorder.onstop = async () => {
                                    const audioBlob = new Blob(this.chunks, { type: 'audio/webm' });
                                    await this.sendToTranscribe(audioBlob);
                                };
                                this.recorder.start();
                                this.isRecording = true;
                                this.status = 'Escuchando...';
                            } catch (err) {
                                console.error('Error al acceder al micrófono:', err);
                                this.status = 'Error de micro';
                            }
                        },

                        stopRecording() {
                            if (this.recorder && this.isRecording) {
                                this.recorder.stop();
                                this.isRecording = false;
                                if (this.stream) {
                                    this.stream.getTracks().forEach(track => track.stop());
                                }
                            }
                        },

                        async sendToTranscribe(blob) {
                            if (!blob || blob.size === 0) {
                                console.error('Blob de audio vacío');
                                this.status = 'Error: Audio vacío';
                                return;
                            }

                            this.status = 'Transcribiendo...';
                            $wire.set('isTranscribing', true);

                            const formData = new FormData();
                            formData.append('audio', blob, 'voice.webm');

                            try {
                                const response = await fetch('/transcribe', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    }
                                });

                                if (!response.ok) {
                                    const errorText = await response.text();
                                    console.error('Error en respuesta del servidor:', response.status, errorText);
                                    throw new Error(`HTTP error! status: ${response.status}`);
                                }

                                const data = await response.json();

                                if (data.ok) {
                                    $wire.dispatch('voiceTranscribed', {
                                        data: data
                                    });
                                } else {
                                    console.error('Error de transcripción:', data.message);
                                    this.status = 'Error: ' + (data.message || 'Fallo');
                                    $wire.set('isTranscribing', false);
                                }
                            } catch (err) {
                                console.error('Error de red o procesamiento:', err);
                                this.status = 'Error de conexión';
                                $wire.set('isTranscribing', false);
                            }
                        }
                     }">
                    <div class="relative">
                        <textarea wire:model="rawInput"
                                  placeholder="Describe lo que ofreces en una frase... ej: 'Ofrezco un tour por los volcanes de 40€'"
                                  class="w-full h-40 bg-slate-50 border-none rounded-2xl p-4 text-sm font-medium focus:ring-2 focus:ring-blue-500/20 transition-all resize-none"></textarea>

                        <template x-if="isRecording">
                            <div
                                class="absolute inset-0 bg-blue-600/90 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center text-white space-y-4 animate-in fade-in zoom-in duration-300">
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 bg-white rounded-full animate-bounce"></span>
                                    <span
                                        class="w-2 h-2 bg-white rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                    <span
                                        class="w-2 h-2 bg-white rounded-full animate-bounce [animation-delay:0.4s]"></span>
                                </div>
                                <span class="font-bold tracking-tight text-lg" x-text="status"></span>
                                <button @click="stopRecording()"
                                        class="mt-4 px-6 py-2 bg-white text-blue-600 rounded-full font-bold text-sm shadow-xl active:scale-95 transition-transform">
                                    Detener
                                </button>
                            </div>
                        </template>

                        <div wire:loading wire:target="isTranscribing"
                             class="absolute inset-0 bg-white/80 backdrop-blur-sm rounded-2xl flex flex-col items-center justify-center text-slate-600 space-y-3">
                            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg"
                                 fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="font-bold text-sm">Transcribiendo voz...</span>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="startRecording()" type="button"
                                class="flex-1 py-4 bg-slate-100 rounded-2xl font-bold text-slate-600 flex items-center justify-center gap-2 hover:bg-slate-200 transition-colors active:scale-95">
                            🎤 Grabar
                        </button>
                        <button wire:click="processWithAI"
                                class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200 flex items-center justify-center gap-2">
                            @if($isProcessing)
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                                     fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Analizando...
                            @else
                                Siguiente
                            @endif
                        </button>
                    </div>
                </div>
            </div>

        @elseif($step === 3)
            <!-- STEP 3: AI Suggestions -->
            <div class="space-y-6">
                <div class="flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-600 p-1.5 rounded-lg text-xs font-bold">IA</span>
                    <h2 class="text-lg font-bold text-slate-800">La IA te ayuda</h2>
                </div>

                <div class="bg-white rounded-[32px] p-6 shadow-sm border border-slate-100 space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Título
                                sugerido</label>
                            <input wire:model="suggestedTitle"
                                   class="w-full bg-slate-50 border-none rounded-xl py-3 px-4 font-bold text-slate-700">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Precio
                                    estimado</label>
                                <div class="relative">
                                    <input type="number" wire:model="suggestedPrice"
                                           class="w-full bg-slate-50 border-none rounded-xl py-3 px-4 font-bold text-slate-700">
                                    <span
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold">€</span>
                                </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Duración
                                    (min)</label>
                                <input type="number" wire:model="suggestedDuration"
                                       class="w-full bg-slate-50 border-none rounded-xl py-3 px-4 font-bold text-slate-700">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Resumen
                                IA</label>
                            <textarea wire:model="suggestedExcerpt"
                                      class="w-full bg-slate-50 border-none rounded-xl py-3 px-4 font-medium text-slate-600 h-24 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="$set('step', 2)"
                                class="flex-1 py-4 bg-slate-50 rounded-2xl font-bold text-slate-400">Editar
                        </button>
                        <button wire:click="nextStep"
                                class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200">
                            Está bien
                        </button>
                    </div>
                </div>
            </div>

        @elseif($step === 4)
            <!-- STEP 4: Photo -->
            <div class="space-y-6">
                <h2 class="text-lg font-bold text-slate-800">Añade una foto</h2>
                <div class="bg-white rounded-[32px] p-6 shadow-sm border border-slate-100 space-y-6">
                    <div
                        class="aspect-video bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 flex flex-col items-center justify-center gap-2 overflow-hidden relative"
                        wire:key="upload-container-{{ $step }}">
                        @if($image && method_exists($image, 'temporaryUrl'))
                            <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                        @else
                            <span class="text-4xl">📸</span>
                            <span class="text-xs font-bold text-slate-400">Toca para subir</span>
                        @endif
                        <input type="file" wire:model.live="image" class="absolute inset-0 opacity-0 cursor-pointer">
                    </div>

                    <div class="flex gap-3">
                        <button wire:click="nextStep"
                                class="flex-1 py-4 bg-slate-50 rounded-2xl font-bold text-slate-400">Saltar
                        </button>
                        <button wire:click="nextStep"
                                class="flex-[2] py-4 bg-blue-600 text-white rounded-2xl font-bold shadow-lg shadow-blue-200">
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>

        @elseif($step === 5)
            <!-- STEP 5: Preview -->
            <div class="space-y-6">
                <h2 class="text-lg font-bold text-slate-800">Vista previa</h2>
                <div class="bg-white rounded-[32px] border border-slate-100 overflow-hidden shadow-xl shadow-slate-200"
                     wire:key="preview-card-{{ $step }}">
                    <div class="h-48 bg-slate-100 relative">
                        @if($image && method_exists($image, 'temporaryUrl'))
                            <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-300">Sin imagen</div>
                        @endif
                        <div
                            class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-tight">
                            Desde {{ $suggestedPrice }}€
                        </div>
                    </div>
                    <div class="p-6 space-y-2">
                        <h3 class="font-bold text-xl leading-tight">{{ $suggestedTitle }}</h3>
                        <p class="text-slate-500 text-sm font-medium">{{ $suggestedExcerpt }}</p>
                    </div>
                </div>

                <button wire:click="submit"
                        class="w-full py-5 bg-slate-900 text-white rounded-[24px] font-bold text-lg shadow-2xl transition-all active:scale-95">
                    Enviar para validación
                </button>
            </div>

        @elseif($step === 6)
            <!-- SUCCESS -->
            <div class="py-12 flex flex-col items-center justify-center text-center space-y-6">
                <div
                    class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-5xl animate-bounce">
                    ✅
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-extrabold text-slate-900">¡Enviado!</h2>
                    <p class="text-slate-500 font-medium">Tu propuesta está en revisión. Ya casi formas parte de
                        TAXILANZ.</p>
                </div>

                <div x-data="{ open: false }" class="w-full bg-white rounded-3xl p-4 border border-slate-100">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between font-bold text-sm text-slate-500">
                        <span>¿Por qué es válida?</span>
                        <span :class="open ? 'rotate-180' : ''">↓</span>
                    </button>
                    <div x-show="open" x-collapse
                         class="pt-4 text-left text-xs text-slate-400 space-y-2 leading-relaxed">
                        <p>• Categoría coherente con el servicio.</p>
                        <p>• Precio competitivo para el mercado local.</p>
                        <p>• La descripción es clara y atractiva para turistas.</p>
                    </div>
                </div>

                <div class="w-full space-y-3 pt-4">
                    <a href="/admin/service-submissions"
                       class="block w-full py-4 bg-slate-900 text-white rounded-2xl font-bold">Ver panel</a>
                    <button wire:click="$set('step', 1)"
                            class="w-full py-4 bg-white border border-slate-200 rounded-2xl font-bold text-slate-600">
                        Crear otra propuesta
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
