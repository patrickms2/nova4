<div
    x-data="{
        recorder: null,
        stream: null,
        chunks: [],
        audioBlob: null,
        status: 'Listo para dictar discos',
        collectionId: @js($getRecord()?->id ?? 0),
        created: 0,
        discs: [],
        photo: null,
        photoPreview: null,

        async start() {
            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true })
            } catch (e) {
                this.status = 'Error: no se pudo acceder al micrófono'
                return
            }

            this.recorder = new MediaRecorder(this.stream)
            this.chunks = []

            this.recorder.ondataavailable = (e) => this.chunks.push(e.data)
            this.recorder.onstop = () => {
                this.audioBlob = new Blob(this.chunks, { type: 'audio/webm' })
                this.status = 'Audio grabado. Pulsa Transcribir.'
            }

            this.recorder.start()
            this.status = 'Grabando... Dicta los discos separados por punto o siguiente'
        },

        stop() {
            if (! this.recorder) return

            this.recorder.stop()

            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop())
            }
        },

        async send() {
            if (! this.audioBlob) {
                this.status = 'No hay audio grabado'
                return
            }

            if (! this.collectionId || this.collectionId <= 0) {
                this.status = 'Error: no se detectó la colección'
                return
            }

            const formData = new FormData()
            formData.append('audio', this.audioBlob, 'voice.webm')
            formData.append('collection_id', this.collectionId)
            if (this.photo) {
                formData.append('photo', this.photo)
            }

            this.status = 'Transcribiendo, aplicando OCR y creando discos...'

            try {
                const response = await fetch(@js(route('admin.voice.transcribe-multiple')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json',
                    },
                    body: formData,
                })

                const data = await response.json()

                if (! data.ok) {
                    this.status = 'Error al transcribir: ' + (data.message || 'desconocido')
                    console.error(data)
                    return
                }

                this.created = data.created || 0
                this.discs = data.discs || []
                this.status = `✅ ${this.created} disco(s) creados (Audio + OCR)`

                if (this.created > 0) {
                    $wire.$refresh()
                    this.$dispatch('refresh-collection-items')
                }
            } catch (e) {
                this.status = 'Error de red: ' + e.message
                console.error(e)
            }
        },

        async removeDisc(index) {
            const disc = this.discs[index]
            if (! disc || ! disc.id) {
                this.discs.splice(index, 1)
                return
            }

            try {
                const response = await fetch(@js(route('admin.voice.delete-disc')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: disc.id }),
                })

                if (response.ok) {
                    this.discs.splice(index, 1)
                    this.created = Math.max(0, this.created - 1)
                    $wire.$refresh()
                    this.$dispatch('refresh-collection-items')
                }
            } catch (e) {
                console.error('Error al borrar disco', e)
            }
        },

        reset() {
            this.audioBlob = null
            this.chunks = []
            this.discs = []
            this.created = 0
            this.photo = null
            this.photoPreview = null
            this.status = 'Listo para dictar discos'
        },

        onPhotoChange(event) {
            const file = event.target.files[0]
            if (file) {
                this.photo = file
                this.photoPreview = URL.createObjectURL(file)
            }
        }
    }"
    class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3"
>
    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        🎙️ Dictado múltiple — separa discos con punto o di "siguiente"
    </div>

    <div class="flex items-center gap-2 flex-wrap">
        <button type="button" x-on:click="start()" class="px-3 py-2 rounded bg-red-600 hover:bg-red-500 text-white text-sm font-medium">
            🔴 Grabar
        </button>

        <button type="button" x-on:click="stop()" class="px-3 py-2 rounded bg-gray-600 hover:bg-gray-500 text-white text-sm font-medium">
            ⏹ Parar
        </button>

        <label class="px-3 py-2 rounded bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium cursor-pointer">
            📷 Foto (Opcional)
            <input type="file" accept="image/*" class="hidden" x-on:change="onPhotoChange($event)">
        </label>

        <button type="button" x-on:click="send()" class="px-3 py-2 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium">
            ✨ Transcribir y crear
        </button>

        <button type="button" x-on:click="reset()" class="px-3 py-2 rounded bg-gray-400 hover:bg-gray-300 text-white text-sm font-medium">
            🔄 Reset
        </button>
    </div>

    <template x-if="photoPreview">
        <div class="mt-2 flex items-center gap-2">
            <img :src="photoPreview" class="w-16 h-16 object-cover rounded border border-gray-200" alt="Vista previa">
            <span class="text-xs text-gray-500">Foto adjunta para reconocimiento</span>
        </div>
    </template>

    <div class="text-sm" x-bind:class="status.startsWith('Error') ? 'text-red-500' : 'text-gray-500 dark:text-gray-400'" x-text="status"></div>

    <template x-if="discs.length > 0">
        <div class="mt-2 space-y-1">
            <div class="text-xs font-semibold text-gray-600 dark:text-gray-400">Discos detectados:</div>
            <template x-for="(disc, i) in discs" :key="i">
                <div class="flex items-center justify-between group rounded p-1 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <div class="text-xs text-gray-500 dark:text-gray-400 pl-2">
                        <span x-text="`${i+1}. ${disc.artist || '?'} — ${disc.title || '?'} ${disc.country ? '(' + disc.country + ')' : ''}${disc.year ? ' ' + disc.year : ''}`"></span>
                    </div>
                    <button type="button" x-on:click="removeDisc(i)" class="text-gray-400 hover:text-red-500 p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </template>
        </div>
    </template>
</div>
