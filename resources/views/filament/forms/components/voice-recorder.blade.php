<div
    x-data="{
        recorder: null,
        stream: null,
        chunks: [],
        audioBlob: null,
        status: 'Listo para grabar',

        async start() {
            this.stream = await navigator.mediaDevices.getUserMedia({ audio: true })
            this.recorder = new MediaRecorder(this.stream)
            this.chunks = []

            this.recorder.ondataavailable = (e) => this.chunks.push(e.data)
            this.recorder.onstop = () => {
                this.audioBlob = new Blob(this.chunks, { type: 'audio/webm' })
                this.status = 'Audio grabado'
            }

            this.recorder.start()
            this.status = 'Grabando...'
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

            const formData = new FormData()
            formData.append('audio', this.audioBlob, 'voice.webm')

            this.status = 'Transcribiendo...'

            const response = await fetch(@js(route('admin.voice.transcribe')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @js(csrf_token()),
                    'Accept': 'application/json',
                },
                body: formData,
            })

            const data = await response.json()

            if (! data.ok) {
                this.status = 'Error al transcribir'
                console.error(data)
                return
            }

            this.fillField('artist', data.artist ?? '')
            this.fillField('title', data.title ?? '')
            this.fillField('country', data.country ?? '')
            this.fillField('year', data.year ?? '')
            this.fillField('voice_input', data.raw_text ?? '')

            this.status = 'Transcripción completada'
        },

        fillField(name, value) {
            const selectors = [
                `[name='data.${name}']`,
                `[name='data[${name}]']`,
                `[wire\\:model*='${name}']`,
                `input[name='${name}']`,
                `textarea[name='${name}']`,
            ]

            let el = null

            for (const selector of selectors) {
                el = document.querySelector(selector)
                if (el) break
            }

            if (! el) {
                console.warn('Campo no encontrado:', name)
                return
            }

            el.value = value
            el.dispatchEvent(new Event('input', { bubbles: true }))
            el.dispatchEvent(new Event('change', { bubbles: true }))
        }
    }"
    class="rounded-xl border p-4 space-y-3"
>
    <div class="flex items-center gap-2">
        <button type="button" x-on:click="start()" class="px-3 py-2 rounded bg-primary-600 text-white">
            Grabar
        </button>

        <button type="button" x-on:click="stop()" class="px-3 py-2 rounded bg-gray-600 text-white">
            Parar
        </button>

        <button type="button" x-on:click="send()" class="px-3 py-2 rounded bg-emerald-600 text-white">
            Transcribir
        </button>
    </div>

    <div class="text-sm text-gray-500" x-text="status"></div>
</div>
