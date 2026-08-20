@php
    $loginVideoWebmUrl = asset('video/login.webm');
    $loginVideoMp4Url = asset('video/login.mp4');
    $loginLandscapeMp4Url = asset('video/login.webm');
@endphp

<div class="portal-login-shell" x-data="portalLoginTheme()" x-init="init()">
    <div class="portal-login-media" aria-hidden="true">
        <video autoplay loop muted playsinline preload="metadata" class="portal-login-video">
            <source src="{{ $loginLandscapeMp4Url }}" type="video/webm">

        </video>
        <div class="portal-login-overlay"></div>
    </div>

    <div class="portal-login-stage">
        <section class="portal-login-card">
            <div class="portal-login-toolbar">
                <button type="button" class="portal-login-toolbar__btn" @click="setTheme('light')" :class="{ 'is-active': theme === 'light' }" aria-label="Tema claro">
                    <svg class="portal-login-toolbar__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z" />
                    </svg>
                </button>

                <button type="button" class="portal-login-toolbar__btn" @click="setTheme('dark')" :class="{ 'is-active': theme === 'dark' }" aria-label="Tema oscuro">
                    <svg class="portal-login-toolbar__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="portal-login-brand">
                <a href="{{ url('/login') }}" class="portal-login-brand__logo">
                    <img src="{{ asset('img/logo.png') }}" alt="Taxilanz" class="portal-login-logo">
                </a>
            </div>

            <header class="portal-login-copy">
                <h1 class="portal-login-title">Portal Taxistas</h1>
                <p class="portal-login-subtitle">Accede con tu NIF o Email</p>
            </header>

            <form wire:submit.prevent="authenticate" class="portal-login-form">
                {{ $this->form }}

                <div class="portal-login-quick">
                    <div class="portal-login-quick__rail">
                        <button
                            type="button"
                            wire:click="quickLogin('emp')"
                            wire:loading.attr="disabled"
                            wire:target="quickLogin"
                            class="portal-login-chip"
                        >
                            <svg class="portal-login-chip__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 8a7 7 0 1 1 14 0H3Z" clip-rule="evenodd" />
                            </svg>
                            <span>EMP</span>
                        </button>

                        <button
                            type="button"
                            wire:click="quickLogin('jma')"
                            wire:loading.attr="disabled"
                            wire:target="quickLogin"
                            class="portal-login-chip"
                        >
                            <svg class="portal-login-chip__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 8a7 7 0 1 1 14 0H3Z" clip-rule="evenodd" />
                            </svg>
                            <span>JMA</span>
                        </button>

                        <button
                            type="button"
                            wire:click="quickLogin('pms')"
                            wire:loading.attr="disabled"
                            wire:target="quickLogin"
                            class="portal-login-chip"
                        >
                            <svg class="portal-login-chip__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-7 8a7 7 0 1 1 14 0H3Z" clip-rule="evenodd" />
                            </svg>
                            <span>PMS</span>
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="authenticate"
                    class="portal-login-submit"
                >
                    <span wire:loading.remove wire:target="authenticate">Acceder al Portal</span>
                    <span wire:loading wire:target="authenticate">Accediendo...</span>
                </button>

                <p class="portal-login-loading" wire:loading wire:target="quickLogin">
                    Cargando credenciales...
                </p>
            </form>
        </section>
    </div>
</div>

<script>
    function portalLoginTheme() {
        return {
            theme: 'dark',
            init() {
                this.theme = window.localStorage.getItem('theme') || 'dark';
                this.applyTheme(this.theme);
            },
            setTheme(theme) {
                this.theme = theme;
                window.localStorage.setItem('theme', theme);
                this.applyTheme(theme);
            },
            applyTheme(theme) {
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.setAttribute('data-theme', theme);
            },
        };
    }
</script>

<style>
    body.fi-body.fi-panel-portal,
    body.fi-body.fi-panel-portal.fi-body,
    html:has(body.fi-body.fi-panel-portal) {
        background: transparent !important;
        background-color: transparent !important;
    }

    body.fi-body.fi-panel-portal .fi-simple-layout,
    body.fi-body.fi-panel-portal .fi-simple-main-ctn,
    body.fi-body.fi-panel-portal .fi-simple-main {
        background: transparent !important;
        background-color: transparent !important;
    }

    body.fi-body.fi-panel-portal .fi-simple-layout {
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    body.fi-body.fi-panel-portal .fi-simple-main-ctn {
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: none !important;
        padding-top: 0 !important;
        padding-bottom: 0 !important;
    }

   body.fi-body.fi-panel-portal .fi-simple-main {
        width: auto !important;
        max-width: fit-content !important;
        display: flex;
        align-items: center;
        box-shadow: none;
        justify-content: center;
        margin: 0 auto !important;
        padding: 0 !important;
    }

    .portal-login-shell {
        position: relative;
            min-width: 400px !important;

        min-height: min(100dvh, 100vh);
    }

    .portal-login-media,
    .portal-login-overlay {
        position: fixed;
        inset: 0;
    }

    .portal-login-media {
        z-index: -2;
        overflow: hidden;
        background:
            radial-gradient(circle at 50% 8%, rgba(212, 220, 240, 0.2), transparent 30%),
            linear-gradient(180deg, rgba(188, 196, 216, 0.88) 0%, rgba(134, 147, 173, 0.44) 32%, rgba(25, 29, 37, 0.38) 56%, rgba(10, 12, 16, 0.72) 100%);
    }

    .portal-login-video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 1;
        filter: saturate(0.9) contrast(0.92) brightness(0.9);
        background:
            radial-gradient(circle at 50% 8%, rgba(212, 220, 240, 0.2), transparent 30%),
            linear-gradient(180deg, rgba(188, 196, 216, 0.88) 0%, rgba(134, 147, 173, 0.44) 32%, rgba(25, 29, 37, 0.38) 56%, rgba(10, 12, 16, 0.72) 100%);
    }

    .portal-login-overlay {
        z-index: -1;
        background:
            linear-gradient(180deg, rgba(205, 212, 228, 0.14) 0%, rgba(21, 25, 33, 0.08) 18%, rgba(8, 10, 14, 0.34) 58%, rgba(6, 8, 12, 0.62) 100%),
            radial-gradient(circle at 50% 12%, rgba(255, 255, 255, 0.12), transparent 30%);
        pointer-events: none;
    }

    .portal-login-stage {
        min-height: min(100dvh, 100vh);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .portal-login-card {
        position: relative;
        width: 100%;
        max-width: 24.6rem;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 1.7rem;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.02) 18%, rgba(17, 17, 20, 0.72) 38%, rgba(17, 17, 20, 0.9) 100%);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow:
            0 28px 80px rgba(0, 0, 0, 0.34),
            inset 0 1px 0 rgba(255, 255, 255, 0.12);
        padding: 1.7rem 1.45rem 1.45rem;
    }

    .portal-login-toolbar {
        position: absolute;
        top: 1.05rem;
        right: 1rem;
        display: inline-flex;
        gap: 0.4rem;
    }

    .portal-login-toolbar__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.7rem;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.8);
        transition:
            background-color 140ms ease,
            border-color 140ms ease,
            color 140ms ease;
    }

   .portal-login-toolbar__btn.is-active {
        color: rgb(235 97 7 / 96%);
    }

    .portal-login-toolbar__icon {
        width: 24px;
        height: 24px;
    }
    input.fi-input {
        appearance: none;
        --tw-border-style: none;
        width: 100%;
        padding-inline:calc(var(--spacing) * 3);padding-block: calc(var(--spacing) * 1.5);
        text-align: start;
        font-size: var(--text-sm);
        line-height: var(--tw-leading,var(--text-sm--line-height));
        --tw-leading: calc(var(--spacing) * 6);
        line-height: calc(var(--spacing) * 6);
        color: var(--gray-950);
        transition-property: color,background-color,border-color,outline-color,text-decoration-color,fill,stroke,--tw-gradient-from,--tw-gradient-via,--tw-gradient-to,opacity,box-shadow,transform,translate,scale,rotate,filter,-webkit-backdrop-filter,backdrop-filter,display,content-visibility,overlay,pointer-events;
        transition-timing-function: var(--tw-ease,var(--default-transition-timing-function));
        transition-duration: var(--tw-duration,var(--default-transition-duration));
        --tw-duration: 75ms;
        background-color: #0000;
        border-style: none;
        transition-duration: 75ms;
        display: block
    }

    input.fi-input: :placeholder {
        color:var(--gray-400)
    }

    input.fi-input: focus {
        --tw-ring-shadow:var(--tw-ring-inset,) 0 0 0 calc(0px + var(--tw-ring-offset-width)) var(--tw-ring-color,currentcolor);
        box-shadow: var(--tw-inset-shadow),var(--tw-inset-ring-shadow),var(--tw-ring-offset-shadow),var(--tw-ring-shadow),var(--tw-shadow);
        --tw-outline-style: none;
        outline-style: none
    }

    input.fi-input: disabled {
        color:var(--gray-500);
        -webkit-text-fill-color: var(--color-gray-500)
    }
    .portal-login-brand {
        display: flex;
        justify-content: flex-start;
        margin-bottom: 1.35rem;
        padding-right: 4.2rem;
    }

    .portal-login-brand__logo {
        display: inline-flex;
        align-items: center;
    }

    .portal-login-logo {
        height: 2.45rem;
        width: auto;
        object-fit: contain;
    }

    .portal-login-copy {
        text-align: center;
        margin-bottom: 1.05rem;
    }

    .portal-login-title {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.03em;
        color: rgba(255, 255, 255, 0.98);
    }

    .portal-login-subtitle {
        margin: 0.45rem 0 0;
        font-size: 0.94rem;
        color: rgba(255, 255, 255, 0.58);
    }

    .portal-login-form {
        display: grid;
        gap: 0.95rem;
    }

    .portal-login-form .fi-fo {
        display: grid;
        gap: 0.9rem;
    }

    .portal-login-form .fi-fo-field-label,
    .portal-login-form .fi-fo-field-label-content {
        color: rgba(255, 255, 255, 0.92) !important;
    }

    .portal-login-form .fi-fo-field-label-content {
        font-size: 0.82rem;
        font-weight: 600;
    }

    .portal-login-form .fi-input-wrp,
    .portal-login-form .fi-input {
        background: rgba(255, 255, 255, 0.07) !important;
        border-color: rgba(255, 255, 255, 0.16) !important;
    }

    .portal-login-form .fi-input {
        color: rgba(255, 255, 255, 0.96) !important;
    }

    .portal-login-form .fi-input::placeholder {
        color: rgba(255, 255, 255, 0.46) !important;
    }

    .portal-login-form .fi-ac {
        display: none;
    }

    .portal-login-quick {
        margin-top: 0.15rem;
    }

    .portal-login-quick__rail {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
        padding: 0.45rem;
        border: 1px solid rgba(255, 255, 255, 0.09);
        border-radius: 1.2rem;
        background: rgba(255, 255, 255, 0.05);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    }

    .portal-login-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-height: 2.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.95rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.78rem;
        font-weight: 700;
        transition:
            transform 140ms ease,
            opacity 140ms ease,
            border-color 140ms ease,
            background-color 140ms ease;
    }

    .portal-login-chip:hover {
        transform: translateY(-1px);
        border-color: rgba(255, 255, 255, 0.18);
    }

    .portal-login-chip:disabled {
        opacity: 0.58;
    }

    .portal-login-chip__icon {
        width: 0.9rem;
        height: 0.9rem;
        opacity: 0.78;
    }

    .portal-login-submit {
        min-height: 3.15rem;
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 0.95rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.04));
        color: rgba(255, 255, 255, 0.98);
        font-size: 0.95rem;
        font-weight: 700;
        box-shadow:
            0 14px 30px rgba(0, 0, 0, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.06);
        transition:
            transform 140ms ease,
            opacity 140ms ease,
            border-color 140ms ease;
    }

    .portal-login-submit:hover {
        transform: translateY(-1px);
        border-color: rgba(255, 255, 255, 0.18);
    }

    .portal-login-submit:disabled {
        opacity: 0.6;
    }

    .portal-login-loading {
        margin: 0;
        text-align: center;
        font-size: 0.82rem;
        color: rgba(255, 255, 255, 0.62);
    }

    @media (max-width: 640px) {
        .portal-login-stage {
            padding: 1rem;
        }

        .portal-login-card {
            max-width: 100%;
            padding: 1.45rem 1rem 1rem;
        }

        .portal-login-title {
            font-size: 1.8rem;
        }
    }
</style>
