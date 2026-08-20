<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Explore | {{ config('app.name', 'Tourist') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite(['resources/css/app.css', 'resources/js/front.js'])
    <style>
        :root {
            --bg: #f7f4ef;
            --ink: #15130f;
            --muted: #716b61;
            --line: rgba(21, 19, 15, .1);
            --panel: rgba(255, 255, 255, .82);
            --panel-solid: #fffdf9;
            --hotel: #2563eb;
            --restaurant: #dc4a25;
            --taxi: #141414;
            --tour-visit: #7c3aed;
            --tour: #7c3aed;
            --taxi-route: #0f766e;
            --transfer: #0891b2;
            --green: #087f5b;
            --shadow: 0 24px 80px rgba(31, 26, 17, .18);

            /* BlatUI / Tailwind theme variables required by calendar & stepper */
            --background: #fffdf9;
            --foreground: #15130f;
            --card: #ffffff;
            --card-foreground: #15130f;
            --muted-foreground: #716b61;
            /* --accent used by BlatUI for day hover; use a subtle warm tint */
            --accent: #f0ece6;
            --accent-foreground: #15130f;
            --primary: #7c3aed;
            --primary-foreground: #ffffff;
            --border: rgba(21,19,15,.12);
            --input: rgba(21,19,15,.12);
            --ring: rgba(124,58,237,.4);
            --destructive: #dc2626;
            --radius: 0.5rem;
        }

        [x-cloak] { display: none !important; }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            overflow: hidden;
            background: var(--bg);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        button,
        input {
            font: inherit;
        }

        .explore-shell {
            position: relative;
            display: grid;
            grid-template-columns: minmax(360px, 430px) minmax(0, 1fr);
            height: 100vh;
            isolation: isolate;
        }

        .map-stage {
            position: relative;
            min-width: 0;
            background: #dfe7dd;
        }

        #exploreMap {
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .leaflet-control-attribution {
            border-radius: 999px 0 0 0;
            padding: 4px 10px;
            background: rgba(255, 255, 255, .72);
            color: #58534b;
            font-size: 10px;
            backdrop-filter: blur(12px);
        }

        .leaflet-popup-content-wrapper {
            overflow: hidden;
            border-radius: 22px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }

        .leaflet-popup-content {
            width: 260px !important;
            margin: 0;
        }

        .leaflet-popup-tip {
            box-shadow: none;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        .sidebar {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            min-width: 0;
            height: 100vh;
            padding: 22px;
            border-right: 1px solid var(--line);
            background:
                radial-gradient(circle at 18% 4%, rgba(255, 255, 255, .9), transparent 30%),
                linear-gradient(160deg, #fffaf2 0%, #f8f4ed 48%, #ece8de 100%);
        }

        .brand-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 26px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            color: inherit;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid rgba(8, 127, 91, .24);
            border-radius: 14px;
            background: #e1f3eb;
            color: var(--accent);
            font-weight: 800;
        }

        .brand-text {
            min-width: 0;
        }

        .brand-title {
            margin: 0;
            overflow: hidden;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0;
            line-height: 1.1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .brand-subtitle {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
        }

        .home-link {
            display: inline-grid;
            width: 42px;
            height: 42px;
            flex: 0 0 auto;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(255, 255, 255, .62);
            color: var(--ink);
            text-decoration: none;
            transition: transform .2s ease, background .2s ease;
        }

        .home-link:hover {
            transform: translateY(-2px);
            background: #fff;
        }

        .hero-copy {
            margin-bottom: 22px;
        }

        .hero-copy h1 {
            margin: 0;
            max-width: 9.5em;
            font-size: clamp(34px, 4vw, 52px);
            line-height: .96;
            letter-spacing: 0;
        }

        .hero-copy p {
            margin: 14px 0 0;
            max-width: 30rem;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .search-wrap {
            position: relative;
            margin-bottom: 14px;
        }

        .search-icon {
            position: absolute;
            top: 50%;
            left: 16px;
            width: 18px;
            height: 18px;
            color: #827a6e;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            height: 54px;
            padding: 0 18px 0 48px;
            border: 1px solid rgba(21, 19, 15, .12);
            border-radius: 18px;
            outline: 0;
            background: rgba(255, 255, 255, .78);
            box-shadow: 0 12px 30px rgba(31, 26, 17, .06);
            color: var(--ink);
            font-size: 14px;
            font-weight: 600;
            transition: border .2s ease, box-shadow .2s ease, background .2s ease;
        }

        .search-input:focus {
            border-color: rgba(8, 127, 91, .42);
            background: #fff;
            box-shadow: 0 16px 40px rgba(8, 127, 91, .12);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .filter-btn {
            display: flex;
            min-width: 0;
            min-height: 78px;
            cursor: pointer;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid rgba(21, 19, 15, .1);
            border-radius: 18px;
            background: rgba(255, 255, 255, .62);
            padding: 12px;
            color: var(--ink);
            text-align: left;
            transition: transform .2s ease, border .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            border-color: color-mix(in srgb, var(--filter-color), transparent 58%);
            background: #fff;
            box-shadow: 0 18px 42px color-mix(in srgb, var(--filter-color), transparent 88%);
        }

        .filter-btn.active {
            border-color: color-mix(in srgb, var(--filter-color), transparent 42%);
            background: #fff;
            box-shadow: 0 18px 42px color-mix(in srgb, var(--filter-color), transparent 88%);
        }

        .filter-btn:not(.active) {
            opacity: .62;
        }

        .filter-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .filter-state {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .filter-dot {
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: var(--filter-color);
            box-shadow: 0 0 0 5px color-mix(in srgb, var(--filter-color), transparent 86%);
        }

        .filter-check {
            display: grid;
            width: 20px;
            height: 20px;
            place-items: center;
            border: 1px solid color-mix(in srgb, var(--filter-color), transparent 48%);
            border-radius: 8px;
            background: color-mix(in srgb, var(--filter-color), white 88%);
            color: var(--filter-color);
            opacity: 0;
            transform: scale(.84);
            transition: opacity .18s ease, transform .18s ease;
        }

        .filter-check svg {
            width: 13px;
            height: 13px;
        }

        .filter-btn.active .filter-check {
            opacity: 1;
            transform: scale(1);
        }

        .filter-count {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
        }

        .filter-label {
            overflow: hidden;
            font-size: 13px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }

        .tool-btn {
            display: inline-flex;
            min-width: 0;
            height: 42px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(21, 19, 15, .1);
            border-radius: 999px;
            background: rgba(255, 255, 255, .66);
            padding: 0 14px;
            color: var(--ink);
            font-size: 12px;
            font-weight: 800;
            transition: transform .2s ease, background .2s ease;
        }

        .tool-btn:hover {
            transform: translateY(-2px);
            background: #fff;
        }

        .tool-btn svg {
            width: 16px;
            height: 16px;
        }

        .results-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 12px;
        }

        .results-head strong {
            font-size: 13px;
        }

        .results-head span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .results-list {
            display: grid;
            flex: 1 1 auto;
            align-content: start;
            gap: 12px;
            overflow: auto;
            min-height: 0;
            padding: 0 4px 28px 0;
            scrollbar-width: thin;
        }

        .place-card {
            display: grid;
            grid-template-columns: 96px minmax(0, 1fr);
            gap: 12px;
            cursor: pointer;
            border: 1px solid rgba(21, 19, 15, .1);
            border-radius: 22px;
            background: rgba(255, 255, 255, .7);
            padding: 10px;
            text-align: left;
            box-shadow: 0 16px 42px rgba(31, 26, 17, .06);
            opacity: 0;
            transform: translateY(12px);
            animation: cardIn .42s ease forwards;
            transition: transform .2s ease, border .2s ease, background .2s ease;
        }

        .place-card:hover,
        .place-card.selected {
            transform: translateY(-3px);
            border-color: color-mix(in srgb, var(--place-color), transparent 58%);
            background: #fff;
        }

        .place-card.unmapped {
            border-style: dashed;
        }

        .place-card img {
            width: 96px;
            height: 116px;
            object-fit: cover;
            border-radius: 16px;
            background: #ded8cc;
        }

        .place-body {
            min-width: 0;
        }

        .place-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .type-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--place-color);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .type-pill::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: currentColor;
        }

        .rating {
            color: #6e5a18;
            font-size: 12px;
            font-weight: 900;
        }

        .map-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border-radius: 999px;
            background: rgba(21, 19, 15, .06);
            padding: 4px 7px;
            color: #514b43;
            font-size: 10px;
            font-weight: 900;
            white-space: nowrap;
        }

        .map-badge.on-map {
            color: var(--place-color);
            background: color-mix(in srgb, var(--place-color), white 88%);
        }

        .place-card h2 {
            margin: 0;
            overflow: hidden;
            font-size: 16px;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .place-card p {
            display: -webkit-box;
            overflow: hidden;
            margin: 7px 0 10px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .mini-facts {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .mini-facts span {
            max-width: 100%;
            overflow: hidden;
            border: 1px solid rgba(21, 19, 15, .08);
            border-radius: 999px;
            background: rgba(247, 244, 239, .78);
            padding: 5px 8px;
            color: #514b43;
            font-size: 11px;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .map-topbar {
            position: absolute;
            z-index: 4;
            top: 22px;
            left: 22px;
            right: 22px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            pointer-events: none;
        }

        .floating-summary,
        .floating-actions {
            border: 1px solid rgba(255, 255, 255, .58);
            border-radius: 24px;
            background: rgba(255, 255, 255, .76);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
            pointer-events: auto;
        }

        .floating-summary {
            max-width: 410px;
            padding: 16px 18px;
        }

        .floating-summary strong {
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .floating-summary span {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
        }

        .floating-actions {
            display: flex;
            gap: 8px;
            padding: 8px;
        }

        .icon-button {
            display: grid;
            width: 42px;
            height: 42px;
            cursor: pointer;
            place-items: center;
            border: 0;
            border-radius: 16px;
            background: transparent;
            color: var(--ink);
            transition: background .2s ease, transform .2s ease;
        }

        .icon-button:hover {
            transform: translateY(-2px);
            background: rgba(8, 127, 91, .1);
        }

        .icon-button svg {
            width: 18px;
            height: 18px;
        }

        .detail-drawer {
            position: absolute;
            z-index: 5;
            right: 24px;
            bottom: 24px;
            width: min(390px, calc(100% - 48px));
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .62);
            border-radius: 28px;
            background: rgba(255, 253, 249, .88);
            box-shadow: var(--shadow);
            opacity: 0;
            transform: translateY(18px) scale(.98);
            pointer-events: none;
            backdrop-filter: blur(18px);
            transition: opacity .25s ease, transform .25s ease;
        }

        .detail-drawer.open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .detail-image {
            width: 100%;
            height: 176px;
            object-fit: cover;
            background: #d7d1c5;
        }

        .detail-content {
            padding: 18px;
        }

        .detail-content h3 {
            margin: 7px 0 8px;
            font-size: 23px;
            line-height: 1.1;
        }

        .detail-content p {
            margin: 0 0 14px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .primary-action,
        .secondary-action {
            display: inline-flex;
            height: 42px;
            cursor: pointer;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 999px;
            padding: 0 16px;
            font-size: 12px;
            font-weight: 900;
            text-decoration: none;
        }

        .primary-action {
            background: var(--ink);
            color: #fff;
        }

        .secondary-action {
            border: 1px solid rgba(21, 19, 15, .12);
            color: var(--ink);
        }

        /* ══════════════════════════════════════════════
           REQUEST MODAL — clean, touch-first, step UX
           ══════════════════════════════════════════════ */
        .request-modal {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            background: rgba(21, 19, 15, .42);
            opacity: 0;
            pointer-events: none;
            backdrop-filter: blur(12px);
            transition: opacity .22s ease;
        }

        @media (min-width: 680px) {
            .request-modal { align-items: center; }
        }

        .request-modal.open {
            opacity: 1;
            pointer-events: auto;
        }

        .request-panel {
            width: 100%;
            max-width: 480px;
            max-height: 94dvh;
            overflow-y: auto;
            overscroll-behavior: contain;
            border: 1px solid rgba(255,255,255,.72);
            border-radius: 28px 28px 0 0;
            background: #fffdf9;
            box-shadow: 0 -8px 48px rgba(21,19,15,.18);
            transform: translateY(24px);
            transition: transform .26s cubic-bezier(.32,1,.32,1);
        }

        @media (min-width: 680px) {
            .request-panel {
                border-radius: 28px;
                box-shadow: 0 24px 80px rgba(21,19,15,.22);
                transform: translateY(18px) scale(.97);
            }
        }

        .request-modal.open .request-panel {
            transform: none;
        }

        /* ── Panel header ── */
        .request-head {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 14px;
            background: #fffdf9;
            border-bottom: 1px solid rgba(21,19,15,.06);
        }

        .request-head-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }

        .request-head h2 {
            margin: 0;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .request-head p { display: none; }

        /* ── Step body ── */
        .request-step-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .step-label {
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .step-question {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.15;
            color: var(--ink);
        }

        /* ── Fields ── */
        .field {
            display: grid;
            gap: 7px;
        }

        .field span {
            color: #514b43;
            font-size: 12px;
            font-weight: 900;
        }

        .field input,
        .field textarea,
        .field select {
            width: 100%;
            border: 1px solid rgba(21,19,15,.12);
            border-radius: 16px;
            outline: 0;
            background: rgba(247,244,239,.78);
            color: var(--ink);
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            transition: border .2s, background .2s, box-shadow .2s;
        }

        .field input, .field select { height: 52px; padding: 0 16px; }

        .field textarea {
            min-height: 80px;
            resize: vertical;
            padding: 14px 16px;
        }

        .field input:focus,
        .field textarea:focus,
        .field select:focus {
            border-color: rgba(8,127,91,.42);
            background: #fff;
            box-shadow: 0 8px 24px rgba(8,127,91,.1);
        }

        /* ── Touch counter ── */
        .touch-counter {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1px solid rgba(21,19,15,.12);
            border-radius: 20px;
            background: rgba(247,244,239,.78);
            overflow: hidden;
        }

        .touch-counter-btn {
            flex: 0 0 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            color: var(--ink);
            background: none;
            border: 0;
            cursor: pointer;
            transition: background .15s;
        }

        .touch-counter-btn:active { background: rgba(21,19,15,.06); }

        .touch-counter-value {
            flex: 1;
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: var(--ink);
        }

        /* ── Slot chips ── */
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .slot-chip {
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(21,19,15,.12);
            border-radius: 16px;
            background: rgba(247,244,239,.74);
            color: var(--ink);
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: border .15s, background .15s, color .15s;
        }

        .slot-chip:disabled {
            opacity: .38;
            cursor: not-allowed;
        }

        .slot-chip.active,
        .slot-chip:not(:disabled):active {
            border-color: var(--chip-color, var(--tour));
            background: var(--chip-color, var(--tour));
            color: #fff;
        }

        .tour-slot-hint {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        /* ── Day strip ── */
        .day-strip {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }

        .day-chip {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            min-height: 64px;
            border: 1.5px solid rgba(21,19,15,.12);
            border-radius: 16px;
            background: rgba(247,244,239,.74);
            color: var(--ink);
            font: inherit;
            cursor: pointer;
            transition: border .15s, background .15s, color .15s;
        }

        .day-chip-wd {
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--muted);
        }

        .day-chip-d {
            font-size: 18px;
            font-weight: 900;
        }

        .day-chip.active,
        .day-chip:active {
            border-color: var(--chip-color, var(--transfer));
            background: var(--chip-color, var(--transfer));
            color: #fff;
        }

        .day-chip.active .day-chip-wd { color: rgba(255,255,255,.7); }

        /* ── Nav buttons ── */
        .step-nav {
            display: flex;
            gap: 10px;
            padding: 0 20px 24px;
        }

        .step-btn-back {
            flex: 0 0 52px;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid rgba(21,19,15,.14);
            border-radius: 16px;
            background: transparent;
            color: var(--ink);
            cursor: pointer;
            transition: background .15s;
        }

        .step-btn-back:active { background: rgba(21,19,15,.06); }

        .step-btn-next {
            flex: 1;
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 0;
            border-radius: 16px;
            background: var(--step-color, var(--tour));
            color: #fff;
            font: inherit;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
        }

        .step-btn-next:disabled { opacity: .38; cursor: not-allowed; }
        .step-btn-next:not(:disabled):active { transform: scale(.97); }

        /* ── Submit footer ── */
        .request-footer {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 20px 24px;
        }

        .request-submit {
            width: 100%;
            height: 56px;
            border: 0;
            border-radius: 18px;
            font: inherit;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: opacity .15s, transform .1s;
        }

        .request-submit:disabled { opacity: .38; cursor: wait; }
        .request-submit:not(:disabled):active { transform: scale(.98); }

        .request-status {
            text-align: center;
            font-size: 12px;
            color: var(--muted);
        }

        .request-status.success { color: var(--green); font-weight: 800; }
        .request-status.error   { color: #b42318;       font-weight: 800; }

        /* ── Date badge (selected date chip) ── */
        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border-radius: 12px;
            background: rgba(8,145,178,.09);
            border: 1px solid rgba(8,145,178,.22);
            font-size: 13px;
            font-weight: 700;
            color: var(--transfer);
            cursor: pointer;
        }

        .date-badge[data-tour] {
            background: rgba(124,58,237,.08);
            border-color: rgba(124,58,237,.2);
            color: var(--tour);
        }

        .date-badge[data-restaurant] {
            background: rgba(220,74,37,.08);
            border-color: rgba(220,74,37,.2);
            color: var(--restaurant);
        }

        /* ── Visibility system ── */

        /* Type-specific fields: hidden until JS adds .active */
        .type-fields { display: none; }
        .type-fields.active { display: contents; }

        /* Contact, summary, footer: hidden by default */
        .contact-fields  { display: none; }
        .summary-fields  { display: none; }
        .finish-fields   { display: none; }
        .request-footer  { display: none; }

        /* Non-tour: show contact + footer immediately (all on one screen) */
        .request-form[data-step="fields"] .contact-fields { display: contents; }
        .request-form[data-step="fields"] .request-footer  { display: flex; flex-direction: column; }

        /* Tour steps controlled by data-tour-current-step */
        .request-form.tour-mode .request-footer { display: none; }
        .request-form.tour-mode[data-tour-current-step="contact"] .contact-fields { display: block; }
        .request-form.tour-mode[data-tour-current-step="contact"] [data-tour-only] { display: block !important; }
        .request-form.tour-mode[data-tour-current-step="contact"] [data-tour-nav]  { display: flex  !important; }
        .request-form.tour-mode[data-tour-current-step="summary"]  .summary-fields { display: block; }
        .request-form.tour-mode[data-tour-current-step="summary"]  .request-footer { display: flex; flex-direction: column; }

        /* Legacy compat selectors */
        .request-form:not(.tour-mode) .tour-step-card   { display: none; }
        .request-form:not(.tour-mode) .tour-step-actions { display: none; }

        /* restaurant slot chip color */
        #restaurantTimeGrid .slot-chip { --chip-color: var(--restaurant); }

        /* legacy compat */
        .tour-step-actions { display: flex; justify-content: flex-end; gap: 8px; }
        .tour-step-next, .tour-step-back { height: 42px; border-radius: 14px; padding: 0 14px; font-size: 13px; font-weight: 900; }
        .tour-step-back { border: 1px solid rgba(21,19,15,.12); background: rgba(255,255,255,.65); color: var(--ink); }
        .tour-step-next { border: 0; background: var(--tour); color: #fff; }
        .tour-step-next:disabled { opacity: .38; cursor: not-allowed; }

        /* slot/time panels */
        .tour-slots-panel { display: none; gap: 9px; }
        .tour-slots-panel.open { display: grid; }
        .transfer-time-panel { display: none; flex-direction: column; gap: 8px; }
        .transfer-time-panel.open { display: flex; }
        .transfer-time-label { font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: var(--muted); }
        .transfer-time-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; max-height: 160px; overflow-y: auto; }
        .transfer-day-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 8px; }
        .transfer-day, .transfer-time { min-width:0; cursor:pointer; border:1px solid rgba(21,19,15,.11); border-radius:14px; background:rgba(247,244,239,.74); color:var(--ink); font:inherit; font-weight:800; transition:border .15s,background .15s,color .15s; }
        .transfer-day { display:grid; gap:2px; min-height:62px; place-items:center; padding:8px; }
        .transfer-day span:first-child { color:var(--muted); font-size:11px; font-weight:900; }
        .transfer-day span:last-child  { font-size:17px; }
        .transfer-time { height:42px; padding:0 10px; font-size:13px; }
        .transfer-day:hover,.transfer-time:hover,.transfer-day.active,.transfer-time.active { border-color:rgba(8,145,178,.45); background:var(--transfer); color:#fff; }
        .transfer-day.active span,.transfer-day:hover span { color:#fff; }
        .transfer-date-summary { display:none; align-items:center; gap:8px; padding:10px 14px; border-radius:12px; background:rgba(8,145,178,.08); border:1px solid rgba(8,145,178,.2); font-size:13px; font-weight:700; color:var(--transfer); cursor:pointer; }
        .transfer-date-summary.visible { display:flex; }
        .guest-selector { display:flex; align-items:center; gap:8px; }
        .guest-selector input { flex:1; text-align:center; font-weight:700; font-size:16px; border:1px solid rgba(21,19,15,.11); border-radius:12px; background:rgba(247,244,239,.74); padding:10px; }
        .guest-btn { width:44px; height:44px; display:flex; align-items:center; justify-content:center; border:1px solid rgba(21,19,15,.11); border-radius:12px; background:rgba(247,244,239,.74); color:var(--ink); font-size:20px; font-weight:700; cursor:pointer; transition:background .15s; }
        .guest-btn:hover { background:var(--restaurant); border-color:var(--restaurant); color:#fff; }
        .tour-stepper { display:grid; grid-template-columns:56px 1fr 56px; align-items:center; gap:10px; border:1px solid rgba(21,19,15,.11); border-radius:18px; background:rgba(247,244,239,.74); padding:10px; }
        .tour-stepper-value { text-align:center; font-size:22px; font-weight:900; }
        .tour-children-select { height:52px; width:100%; border:1px solid rgba(21,19,15,.12); border-radius:16px; outline:0; background:rgba(247,244,239,.78); color:var(--ink); font:inherit; font-size:15px; font-weight:700; padding:0 16px; }

        .marker-pin {
            position: relative;
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border: 3px solid #fff;
            border-radius: 18px 18px 18px 6px;
            background: var(--pin-color);
            box-shadow: 0 16px 34px rgba(0, 0, 0, .24);
            color: #fff;
            transform: rotate(-45deg);
        }

        .marker-pin svg {
            width: 19px;
            height: 19px;
            transform: rotate(45deg);
        }

        .marker-pin.active {
            animation: pulsePin 1.2s ease-in-out infinite;
        }

        .popup-card img {
            display: block;
            width: 100%;
            height: 118px;
            object-fit: cover;
        }

        .popup-card div {
            padding: 12px;
        }

        .popup-card strong {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .popup-card span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .empty-state {
            border: 1px dashed rgba(21, 19, 15, .18);
            border-radius: 22px;
            padding: 22px;
            background: rgba(255, 255, 255, .52);
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .loading-skeleton {
            height: 138px;
            overflow: hidden;
            border-radius: 22px;
            background: linear-gradient(100deg, rgba(255,255,255,.42), rgba(255,255,255,.92), rgba(255,255,255,.42));
            background-size: 220% 100%;
            animation: shimmer 1.3s ease infinite;
        }

        @keyframes shimmer {
            from { background-position: 120% 0; }
            to { background-position: -120% 0; }
        }

        @keyframes cardIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulsePin {
            0%, 100% { box-shadow: 0 16px 34px rgba(0, 0, 0, .24), 0 0 0 0 color-mix(in srgb, var(--pin-color), transparent 50%); }
            50% { box-shadow: 0 18px 40px rgba(0, 0, 0, .26), 0 0 0 14px color-mix(in srgb, var(--pin-color), transparent 100%); }
        }

        @media (max-width: 980px) {
            body {
                overflow: auto;
            }

            .explore-shell {
                display: flex;
                min-height: 100vh;
                height: auto;
                flex-direction: column;
            }

            .sidebar {
                height: auto;
                min-height: 0;
                border-right: 0;
                border-bottom: 1px solid var(--line);
                padding: 18px;
            }

            .hero-copy h1 {
                max-width: none;
                font-size: 34px;
            }

            .map-stage {
                height: 68vh;
                min-height: 540px;
            }

            .results-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                overflow: visible;
                padding-right: 0;
            }

            .place-card {
                grid-template-columns: 86px minmax(0, 1fr);
            }

            .place-card img {
                width: 86px;
                height: 112px;
            }
        }

        @media (max-width: 680px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-btn {
                min-height: 62px;
            }

            .toolbar {
                flex-wrap: wrap;
            }

            .results-list {
                grid-template-columns: 1fr;
            }

            .map-stage {
                height: 72vh;
                min-height: 520px;
            }

            .map-topbar {
                top: 14px;
                left: 14px;
                right: 14px;
                flex-direction: column;
            }

            .floating-summary {
                max-width: none;
            }

            .floating-actions {
                align-self: flex-end;
            }

            .detail-drawer {
                right: 14px;
                bottom: 14px;
                width: calc(100% - 28px);
            }

            .request-grid {
                grid-template-columns: 1fr;
            }

            .tour-day-grid,
            .tour-slot-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .transfer-day-grid,
            .transfer-time-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
                .from-slate-900{--tw-gradient-from: #1b1b1b;--tw-gradient-stops:var(--tw-gradient-via-stops,var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))}
       .from-slate-900{--tw-gradient-from: #1b1b1b;--tw-gradient-stops:var(--tw-gradient-via-stops,var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))}
    .bg-slate-900 {
        background-color: #3c3e42 !important;
    }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
    @livewireMapStyles
</head>

<body class="bg-background text-slate-600 text-foreground antialiased">
    {{ $slot }}
    @livewireScripts
    @livewireMapScripts
    <!-- Contenedor Raíz de Alta Fidelidad (Une los dos niveles en una fila flexible) -->
<div x-data="{ 
        activeTab: $persist('facturacion'), 
        sidebarOpen: $persist(true) 
     }" 
     class="flex flex-row h-screen w-screen overflow-hidden bg-slate-950 text-slate-100" 
     data-name="Two Level Sidebar">
    
    <!-- ========================================== -->
    <!-- NIVEL 1: BARRA DE ICONOS ULTRA-ESTRECHA    -->
    <!-- ========================================== -->
    <aside class="flex flex-col justify-between w-18 h-full bg-slate-950 border-r border-white/5 items-center py-5 shrink-0 z-40 relative shadow-[10px_0_30px_rgba(0,0,0,0.5)]">
        <!-- Branding Superior -->
        <div class="flex flex-col gap-8 items-center w-full px-2">
            <div class="p-2.5 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/10 group cursor-pointer">
                <x-lucide-file-text class="w-5 h-5 text-white" />
            </div>
            
            <!-- Navegación de Macros/Módulos -->
            <nav class="flex flex-col gap-2.5 w-full items-center">
                <!-- Botón Core de Facturación (Activo) -->
                <button @click="activeTab = 'facturacion'; sidebarOpen = true"
                        :class="activeTab === 'facturacion' ? 'bg-white/10 text-indigo-400 border-indigo-500/40' : 'text-slate-400 hover:text-white border-transparent'"
                        class="p-3 rounded-xl flex justify-center items-center transition-all duration-300 border cursor-pointer relative group">
                    <x-lucide-layers class="w-5 h-5" />
                    <span class="absolute left-20 bg-slate-900 border border-white/10 text-white text-xs px-2.5 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-50">
                        Gestión Comercial
                    </span>
                </button>

                <!-- Botón de Inteligencia Artificial / OCR -->
                <button @click="activeTab = 'ia'; sidebarOpen = true"
                        :class="activeTab === 'ia' ? 'bg-white/10 text-indigo-400 border-indigo-500/40' : 'text-slate-400 hover:text-white border-transparent'"
                        class="p-3 rounded-xl flex justify-center items-center transition-all duration-300 border cursor-pointer relative group">
                    <x-lucide-cpu class="w-5 h-5" />
                    <span class="absolute left-20 bg-slate-900 border border-white/10 text-white text-xs px-2.5 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-50">
                        Automatización OCR
                    </span>
                </button>
            </nav>
        </div>

        <!-- Grupo Inferior (Ajustes y Admin de tu captura) -->
        <div class="flex flex-col gap-4 items-center w-full px-2">
            <button class="p-3 text-slate-400 hover:text-white rounded-xl flex justify-center transition-all cursor-pointer">
                <x-lucide-settings class="w-5 h-5" />
            </button>
            <div class="h-px w-6 bg-white/5"></div>
            <!-- Avatar de Admin -->
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-pink-500 p-[1px] cursor-pointer shadow-md">
                <div class="w-full h-full bg-slate-900 rounded-xl flex items-center justify-center text-xs font-bold text-white uppercase">
                    Ad
                </div>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- NIVEL 2: SUBMENÚ DETALLADO DESPLEGABLE    -->
    <!-- ========================================== -->
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400"
           x-transition:enter-start="w-0 opacity-0"
           x-transition:enter-end="w-64 opacity-100"
           x-transition:leave="transition ease-in duration-300"
           x-transition:leave-end="w-0 opacity-0"
           class="w-64 h-full bg-slate-950/40 backdrop-blur-xl border-r border-white/5 flex flex-col py-6 px-4 shrink-0 z-30 shadow-[10px_0_30px_rgba(0,0,0,0.2)]">
        
        <!-- SUBMENÚ ASOCIADO A TU PANEL (Facturación) -->
        <div x-show="activeTab === 'facturacion'" class="flex flex-col h-full w-full space-y-6">
            <!-- Título NovaFactu de tu imagen -->
            <div class="px-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">NovaFactu</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Nova Hub</p>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]"></span>
                </div>
                
                <!-- Buscador Integrado -->
                <div class="mt-4 relative">
                    <x-lucide-search class="w-3.5 h-3.5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input type="text" placeholder="Buscar..." class="w-full bg-white/5 border border-white/5 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                </div>
            </div>

            <!-- Listado de Acciones y Módulos de tu captura -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-1 hidden-scrollbar">
                <!-- Bloque Acciones -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Acciones</p>
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-indigo-400 bg-indigo-500/5 hover:bg-indigo-500/10 rounded-xl transition-all border border-indigo-500/10">
                        <x-lucide-plus class="w-3.5 h-3.5" />
                        <span>Nueva factura</span>
                    </a>
                </div>

                <!-- Bloque Módulos Esenciales -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Módulos</p>
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-layout-dashboard class="w-4 h-4 text-slate-500" />
                        <span>Dashboard</span>
                    </a>

                    <a href="#" class="flex items-center justify-between px-3 py-2 text-xs font-bold text-white bg-white/5 rounded-xl transition-all border border-white/5">
                        <div class="flex items-center gap-2.5">
                            <x-lucide-file-text class="w-4 h-4 text-indigo-400" />
                            <span>Facturas</span>
                        </div>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-wallet class="w-4 h-4 text-slate-500" />
                        <span>Gastos</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-layers class="w-4 h-4 text-slate-500" />
                        <span>Remesas</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-users class="w-4 h-4 text-slate-500" />
                        <span>Clientes</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-building class="w-4 h-4 text-slate-500" />
                        <span>Empresas</span>
                    </a>
                </div>

                <!-- Bloque Avanzado y Sistemas Legales -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sistemas</p>
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-scan-face class="w-4 h-4 text-slate-500" />
                        <span>OCR Inteligente</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/5 rounded-xl transition-all">
                        <x-lucide-shield-check class="w-4 h-4 text-emerald-500/70" />
                        <span>VeriFactu Activo</span>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- ÁREA DE CONTENIDO CENTRAL (WORKSPACE)      -->
    <!-- ========================================== -->
    <main class="flex-1 flex flex-col h-full min-w-0 bg-slate-900/40 relative overflow-y-auto">
        <!-- Barra de Control Superior -->
        <header class="h-16 border-b border-white/5 flex items-center px-6 justify-between shrink-0 bg-slate-950/20 backdrop-blur-md">

</body>
</html>
