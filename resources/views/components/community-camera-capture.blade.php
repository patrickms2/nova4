@props([
    'label' => 'Fotografía',
    'required' => false,
    'filenamePrefix' => 'incidencia',
])

<section
    x-data="{
        stream: null,
        previewUrl: null,
        cameraOpen: false,
        photoReady: false,
        uploading: false,
        error: '',
        async openCamera() {
            this.error = '';

            if (! navigator.mediaDevices?.getUserMedia) {
                this.error = 'Este dispositivo no permite abrir la cámara desde el navegador.';
                return;
            }

            try {
                this.stopCamera();
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' } },
                    audio: false,
                });
                this.cameraOpen = true;
                await this.$nextTick();
                this.$refs.cameraVideo.srcObject = this.stream;
                await this.$refs.cameraVideo.play();
            } catch (error) {
                this.cameraOpen = false;
                this.error = 'No se pudo abrir la cámara. Revisa el permiso de cámara del navegador.';
            }
        },
        takePhoto() {
            const video = this.$refs.cameraVideo;

            if (! video?.videoWidth || ! video?.videoHeight) {
                this.error = 'La cámara todavía no está preparada. Inténtalo de nuevo.';
                return;
            }

            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            this.uploading = true;

            canvas.toBlob(blob => {
                if (! blob) {
                    this.uploading = false;
                    this.error = 'No se pudo capturar la fotografía.';
                    return;
                }

                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = URL.createObjectURL(blob);
                const filenamePrefix = @js($filenamePrefix);
                const filename = `${filenamePrefix}-${Date.now()}.jpg`;

                this.$wire.$upload('entryFile', new File([blob], filename, { type: 'image/jpeg' }), () => {
                    this.uploading = false;
                    this.photoReady = true;
                    this.stopCamera();
                }, () => {
                    this.uploading = false;
                    this.error = 'No se pudo subir la fotografía. Inténtalo de nuevo.';
                });
            }, 'image/jpeg', 0.9);
        },
        async retake() {
            this.photoReady = false;
            this.$wire.set('entryFile', null);

            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }

            await this.openCamera();
        },
        stopCamera() {
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.cameraOpen = false;
        },
        resetCamera() {
            this.stopCamera();
            this.photoReady = false;
            this.uploading = false;
            this.error = '';

            if (this.previewUrl) {
                URL.revokeObjectURL(this.previewUrl);
                this.previewUrl = null;
            }
        },
    }"
    x-on:community-camera-reset.window="resetCamera()"
    class="grid gap-3 text-sm sm:col-span-2"
>
    <div class="flex items-center justify-between gap-3">
        <span>{{ $label }} @if ($required)<b class="text-red-400">*</b>@else<small class="text-white/35">(opcional)</small>@endif</span>
        <span x-show="photoReady" class="rounded-full border border-emerald-400/25 bg-emerald-500/10 px-2.5 py-1 text-[11px] font-bold text-emerald-300">PREPARADA</span>
    </div>

    <div class="overflow-hidden rounded-2xl border border-white/15 bg-black/25 shadow-[inset_0_1px_0_rgba(255,255,255,0.06),0_16px_45px_rgba(0,0,0,0.28)]">
        <button
            x-show="! cameraOpen && ! photoReady && ! uploading"
            type="button"
            x-on:click="openCamera()"
            class="group flex min-h-44 w-full flex-col items-center justify-center gap-3 bg-[radial-gradient(circle_at_center,rgba(220,38,38,0.13),transparent_58%)] p-6 transition duration-300 hover:bg-red-500/10"
        >
            <span class="flex h-16 w-16 items-center justify-center rounded-full border border-red-400/30 bg-red-600 shadow-[0_0_35px_rgba(220,38,38,0.22)] transition group-hover:scale-105 group-hover:bg-red-500">
                <x-heroicon-o-camera class="h-8 w-8" />
            </span>
            <span class="font-bold">Abrir cámara</span>
            <small class="text-white/40">Usaremos preferentemente la cámara trasera</small>
        </button>

        <div x-show="cameraOpen" x-cloak class="relative bg-black">
            <video x-ref="cameraVideo" autoplay playsinline muted class="max-h-[52vh] min-h-64 w-full object-cover"></video>
            <div class="absolute inset-x-0 bottom-0 flex justify-center bg-gradient-to-t from-black/90 to-transparent px-4 pb-5 pt-12">
                <button type="button" x-on:click="takePhoto()" class="group flex h-16 w-16 items-center justify-center rounded-full border-4 border-white bg-white/25 shadow-xl transition hover:scale-105" aria-label="Sacar fotografía">
                    <span class="h-11 w-11 rounded-full bg-white transition group-hover:bg-red-100"></span>
                </button>
            </div>
        </div>

        <div x-show="uploading" x-cloak class="flex min-h-44 items-center justify-center gap-3 text-blue-300">
            <span class="h-5 w-5 animate-spin rounded-full border-2 border-blue-300/30 border-t-blue-300"></span>
            Preparando fotografía…
        </div>

        <div x-show="photoReady" x-cloak class="relative bg-black">
            <img x-bind:src="previewUrl" alt="Fotografía de la incidencia" class="max-h-[52vh] min-h-52 w-full object-contain">
            <div class="absolute inset-x-0 bottom-0 flex justify-center bg-gradient-to-t from-black/90 to-transparent px-4 pb-4 pt-12">
                <button type="button" x-on:click="retake()" class="community-button community-button-muted"><x-heroicon-o-arrow-path class="h-4 w-4" /> Repetir foto</button>
            </div>
        </div>
    </div>

    <p x-show="error" x-text="error" class="rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-red-300"></p>
    @error('entryFile')<small class="text-red-300">{{ $message }}</small>@enderror
</section>
