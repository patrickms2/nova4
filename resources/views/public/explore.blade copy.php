<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Explore | {{ config('app.name', 'Tourist') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
            --accent: #087f5b;
            --shadow: 0 24px 80px rgba(31, 26, 17, .18);
        }

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

        .request-modal {
            position: fixed;
            inset: 0;
            z-index: 30;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(21, 19, 15, .34);
            opacity: 0;
            pointer-events: none;
            backdrop-filter: blur(16px);
            transition: opacity .22s ease;
        }

        .request-modal.open {
            opacity: 1;
            pointer-events: auto;
        }

        .request-panel {
            width: min(760px, 100%);
            max-height: min(760px, calc(100vh - 40px));
            overflow: auto;
            border: 1px solid rgba(255, 255, 255, .72);
            border-radius: 28px;
            background: #fffdf9;
            box-shadow: var(--shadow);
            transform: translateY(18px) scale(.98);
            transition: transform .22s ease;
        }

        .request-modal.open .request-panel {
            transform: translateY(0) scale(1);
        }

        .request-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px 0;
        }

        .request-head h2 {
            margin: 6px 0 6px;
            font-size: 28px;
            line-height: 1.05;
        }

        .request-head p,
        .request-status {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.5;
        }

        .request-form {
            display: grid;
            gap: 16px;
            padding: 18px 22px 22px;
        }

        .request-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .field {
            display: grid;
            gap: 7px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        .field span {
            color: #514b43;
            font-size: 12px;
            font-weight: 900;
        }

        .field input,
        .field textarea {
            width: 100%;
            border: 1px solid rgba(21, 19, 15, .12);
            border-radius: 16px;
            outline: 0;
            background: rgba(247, 244, 239, .78);
            color: var(--ink);
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            transition: border .2s ease, background .2s ease, box-shadow .2s ease;
        }

        .field input {
            height: 46px;
            padding: 0 13px;
        }

        .field textarea {
            min-height: 88px;
            resize: vertical;
            padding: 12px 13px;
        }

        .field input:focus,
        .field textarea:focus {
            border-color: rgba(8, 127, 91, .42);
            background: #fff;
            box-shadow: 0 12px 28px rgba(8, 127, 91, .1);
        }

        .contact-fields,
        .finish-fields {
            display: contents;
        }

        .type-fields {
            display: none;
        }

        .type-fields.active {
            display: contents;
        }

        .tour-step-card {
            grid-column: 1 / -1;
            display: grid;
            gap: 14px;
            border: 1px solid rgba(124, 58, 237, .14);
            border-radius: 18px;
            background: rgba(255, 255, 255, .55);
            padding: 14px;
        }

        .tour-step-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .tour-step-kicker {
            color: var(--tour);
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .tour-step-title {
            margin-top: 3px;
            color: var(--ink);
            font-size: 15px;
            font-weight: 900;
        }

        .tour-step-index {
            display: grid;
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 14px;
            background: rgba(124, 58, 237, .1);
            color: var(--tour);
            font-size: 13px;
            font-weight: 900;
        }

        .tour-step-subtitle {
            margin-top: -6px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
        }

        .tour-attendees-grid {
            display: grid;
            gap: 12px;
        }

        .tour-attendees-block {
            display: grid;
            gap: 8px;
        }

        .tour-attendees-label {
            color: var(--muted);
            font-size: 12px;
            font-weight: 900;
        }

        .tour-stepper {
            display: grid;
            grid-template-columns: 44px 1fr 44px;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(21, 19, 15, .11);
            border-radius: 18px;
            background: rgba(247, 244, 239, .74);
            padding: 10px;
        }

        .tour-stepper-value {
            text-align: center;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0;
        }

        .tour-children-select {
            height: 46px;
            width: 100%;
            border: 1px solid rgba(21, 19, 15, .12);
            border-radius: 16px;
            outline: 0;
            background: rgba(247, 244, 239, .78);
            color: var(--ink);
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            padding: 0 13px;
        }

        .tour-day-grid,
        .tour-slot-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .tour-day,
        .tour-slot {
            min-width: 0;
            cursor: pointer;
            border: 1px solid rgba(21, 19, 15, .11);
            border-radius: 14px;
            background: rgba(247, 244, 239, .74);
            color: var(--ink);
            font: inherit;
            font-weight: 800;
            transition: border .2s ease, background .2s ease, color .2s ease, transform .2s ease;
        }

        .tour-day {
            display: grid;
            gap: 2px;
            min-height: 62px;
            place-items: center;
            padding: 8px;
        }

        .tour-day span:first-child {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
        }

        .tour-day span:last-child {
            font-size: 17px;
        }

        .tour-slot {
            height: 42px;
            padding: 0 10px;
        }

        .tour-slot:disabled {
            cursor: not-allowed;
            opacity: 0.45;
        }

        .tour-slot:disabled:hover {
            transform: none;
            border-color: rgba(21, 19, 15, .11);
            background: rgba(247, 244, 239, .74);
            color: var(--ink);
        }

        .tour-day:hover,
        .tour-slot:hover,
        .tour-day.active,
        .tour-slot.active {
            transform: translateY(-1px);
            border-color: rgba(124, 58, 237, .42);
            background: var(--tour);
            color: #fff;
        }

        .tour-day.active span,
        .tour-day:hover span {
            color: #fff;
        }

        /* Transfer day/time picker */
        .transfer-day-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            grid-column: 1 / -1;
        }

        .transfer-time-panel {
            display: none;
            flex-direction: column;
            gap: 8px;
            grid-column: 1 / -1;
        }

        .transfer-time-panel.open {
            display: flex;
        }

        .transfer-time-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .transfer-time-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 6px;
            max-height: 188px;
            overflow-y: auto;
            padding-right: 2px;
        }

        .transfer-day,
        .transfer-time {
            min-width: 0;
            cursor: pointer;
            border: 1px solid rgba(21, 19, 15, .11);
            border-radius: 14px;
            background: rgba(247, 244, 239, .74);
            color: var(--ink);
            font: inherit;
            font-weight: 800;
            transition: border .2s ease, background .2s ease, color .2s ease, transform .2s ease;
        }

        .transfer-day {
            display: grid;
            gap: 2px;
            min-height: 62px;
            place-items: center;
            padding: 8px;
        }

        .transfer-day span:first-child {
            color: var(--muted);
            font-size: 11px;
            font-weight: 900;
        }

        .transfer-day span:last-child {
            font-size: 17px;
        }

        .transfer-time {
            height: 42px;
            padding: 0 10px;
            font-size: 13px;
        }

        .transfer-day:hover,
        .transfer-time:hover,
        .transfer-day.active,
        .transfer-time.active {
            transform: translateY(-1px);
            border-color: rgba(8, 145, 178, .45);
            background: var(--transfer);
            color: #fff;
        }

        .transfer-day.active span,
        .transfer-day:hover span {
            color: #fff;
        }

        .transfer-date-summary {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(8, 145, 178, .08);
            border: 1px solid rgba(8, 145, 178, .2);
            font-size: 13px;
            font-weight: 700;
            color: var(--transfer);
            grid-column: 1 / -1;
            cursor: pointer;
        }

        .transfer-date-summary.visible {
            display: flex;
        }

        /* Restaurant guest selector */
        .guest-selector {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .guest-selector input {
            flex: 1;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
            padding: 10px;
            border: 1px solid rgba(21, 19, 15, .11);
            border-radius: 12px;
            background: rgba(247, 244, 239, .74);
        }

        .guest-btn {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(21, 19, 15, .11);
            border-radius: 12px;
            background: rgba(247, 244, 239, .74);
            color: var(--ink);
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: border .2s ease, background .2s ease, color .2s ease, transform .2s ease;
        }

        .guest-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(220, 74, 37, .45);
            background: var(--restaurant);
            color: #fff;
        }

        .tour-slots-panel {
            display: none;
            gap: 9px;
        }

        .tour-slots-panel.open {
            display: grid;
        }

        .tour-slot-hint {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.4;
        }

        .tour-step-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .tour-step-next,
        .tour-step-back {
            height: 42px;
            border-radius: 14px;
            padding: 0 14px;
            font-size: 13px;
            font-weight: 900;
        }

        .tour-step-back {
            border: 1px solid rgba(21, 19, 15, .12);
            background: rgba(255, 255, 255, .65);
            color: var(--ink);
        }

        .tour-step-next {
            border: 0;
            background: var(--tour);
            color: #fff;
        }

        .tour-step-next:disabled {
            cursor: not-allowed;
            opacity: .52;
        }

        .request-form.tour-mode .contact-fields,
        .request-form.tour-mode .finish-fields,
        .request-form.tour-mode [data-request-fields="tour"] {
            display: none;
        }

        .request-form.tour-mode[data-tour-current-step="calendar"] [data-request-fields="tour"],
        .request-form.tour-mode[data-tour-current-step="calendar"] [data-tour-step="calendar"],
        .request-form.tour-mode[data-tour-current-step="contact"] .contact-fields,
        .request-form.tour-mode[data-tour-current-step="summary"] .summary-fields {
            display: contents;
        }

        .request-form.tour-mode .request-footer {
            display: none;
        }

        .request-form.tour-mode[data-tour-current-step="summary"] .request-footer {
            display: flex;
        }

        .request-form:not(.tour-mode) .tour-step-card {
            display: none;
        }

        .request-form:not(.tour-mode) .tour-step-actions {
            display: none;
        }

        .request-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .request-status.success {
            color: var(--accent);
            font-weight: 800;
        }

        .request-status.error {
            color: #b42318;
            font-weight: 800;
        }

        .request-submit {
            min-width: 178px;
            border: 0;
            cursor: pointer;
        }

        .request-submit:disabled {
            cursor: wait;
            opacity: .66;
        }

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
</head>
<body>
    <div class="explore-shell">
        <aside class="sidebar">
            <div class="brand-row">
                <a class="brand" href="{{ url('/') }}" aria-label="Go home">
                    <span class="brand-mark">T</span>
                    <span class="brand-text">
                        <span class="brand-title">{{ config('app.name', 'Tourist') }}</span>
                        <span class="brand-subtitle">Public explore map</span>
                    </span>
                </a>
                <a class="home-link" href="{{ url('/') }}" title="Home" aria-label="Home">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
                </a>
            </div>

            <section class="hero-copy">
                <p>Explore hotels, restaurants and taxi services on one live map. </p>
            </section>

            <div class="search-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="searchInput" class="search-input" type="search" placeholder="Search by name, city or service" autocomplete="off">
            </div>

            <div class="filter-grid" aria-label="Explore filters">
                <button class="filter-btn" style="--filter-color: var(--hotel)" data-filter="hotel" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="hotel">0</span>
                    </span>
                    <span class="filter-label">Hotels</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--restaurant)" data-filter="restaurant" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="restaurant">0</span>
                    </span>
                    <span class="filter-label">Restaurants</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--taxi)" data-filter="taxi" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="taxi">0</span>
                    </span>
                    <span class="filter-label">Taxi</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--tour-visit)" data-filter="tour_visit" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="tour_visit">0</span>
                    </span>
                    <span class="filter-label">Visit Tour</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--taxi-route)" data-filter="taxi_route" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="taxi_route">0</span>
                    </span>
                    <span class="filter-label">Taxi Route</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--transfer)" data-filter="transfer" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="transfer">0</span>
                    </span>
                    <span class="filter-label">Transfer</span>
                </button>
            </div>

            <div class="toolbar">
                <button id="fitMapBtn" class="tool-btn" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M16 3h3a2 2 0 0 1 2 2v3"/><path d="M8 21H5a2 2 0 0 1-2-2v-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                    Fit map
                </button>
                <button id="nearMeBtn" class="tool-btn" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v3"/><path d="M12 19v3"/><path d="M2 12h3"/><path d="M19 12h3"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/></svg>
                    Near me
                </button>
            </div>

            <div class="results-head">
                <strong>List results</strong>
                <span id="resultsCount">Loading</span>
            </div>
            <div id="resultsList" class="results-list">
                <div class="loading-skeleton"></div>
                <div class="loading-skeleton"></div>
                <div class="loading-skeleton"></div>
            </div>
        </aside>

        <main class="map-stage">
            <div id="exploreMap" aria-label="Public map"></div>
            <div class="map-topbar">
                <div class="floating-summary">
                    <strong id="mapTitle">Live tourism map</strong>
                    <span id="mapSubtitle">Loading public hotels, restaurants and taxi services with real coordinates.</span>
                </div>
                <div class="floating-actions">
                    <button id="clearSelectionBtn" class="icon-button" type="button" title="Clear selection" aria-label="Clear selection">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
            <section id="detailDrawer" class="detail-drawer" aria-live="polite"></section>
        </main>
    </div>

    <section id="requestModal" class="request-modal" aria-hidden="true">
        <div class="request-panel" role="dialog" aria-modal="true" aria-labelledby="requestTitle">
            <div class="request-head">
                <div>
                    <span id="requestType" class="type-pill">Request</span>
                    <h2 id="requestTitle">Reserve</h2>
                    <p id="requestSubtitle">Send a direct request to the responsible manager. They can approve or cancel it from their panel.</p>
                </div>
                <button id="closeRequestModal" class="icon-button" type="button" title="Close" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form id="requestForm" class="request-form">
                <div class="request-grid">
                    <div class="type-fields" data-request-fields="tour">
                        <section class="tour-step-card" data-tour-step="calendar">
                            <div class="tour-step-top">
                                <div>
                                    <div class="tour-step-kicker">Step 1</div>
                                    <div class="tour-step-title">Choose day and time</div>
                                </div>
                                <span class="tour-step-index">1</span>
                            </div>
                            <div class="tour-day-grid" aria-label="Tour days">
                                <button class="tour-day" data-tour-day type="button"><span>Today</span><span></span></button>
                                <button class="tour-day" data-tour-day type="button"><span>Tomorrow</span><span></span></button>
                                <button class="tour-day" data-tour-day type="button"><span></span><span></span></button>
                                <button class="tour-day" data-tour-day type="button"><span></span><span></span></button>
                            </div>
                            <div id="tourSlotsPanel" class="tour-slots-panel" aria-live="polite">
                                <p id="tourSlotHint" class="tour-slot-hint">Select a day to see available slots.</p>
                                <div class="tour-slot-grid" aria-label="Tour time slots"></div>
                            </div>
                            <div class="tour-step-actions">
                                <button class="tour-step-next" data-tour-next="attendees" type="button" disabled>Continue</button>
                            </div>
                        </section>

                        <section class="tour-step-card" data-tour-step="attendees">
                            <div class="tour-step-top">
                                <div>
                                    <div class="tour-step-kicker">Step 2</div>
                                    <div class="tour-step-title">How many people?</div>
                                </div>
                                <span class="tour-step-index">2</span>
                            </div>

                            <input name="adults" type="hidden" value="2">
                            <input name="children" type="hidden" value="0">
                            <input name="tour_date" type="hidden">
                            <input name="tour_schedule" type="hidden">

                            <div class="tour-attendees-grid" aria-label="Attendees selection">
                                <div class="tour-attendees-block">
                                    <div class="tour-attendees-label">Adults</div>
                                    <div class="tour-stepper" role="group" aria-label="Adults">
                                        <button class="icon-button" type="button" data-stepper-minus="adults" aria-label="Decrease adults">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/></svg>
                                        </button>
                                        <div class="tour-stepper-value" data-stepper-value="adults">2</div>
                                        <button class="icon-button" type="button" data-stepper-plus="adults" aria-label="Increase adults">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="tour-attendees-block">
                                    <div class="tour-attendees-label">Children (free &lt; 15)</div>
                                    <label class="field full">
                                        <select class="tour-children-select" data-children-select aria-label="Number of children">
                                            <option value="0" selected>0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                            <option value="6">6</option>
                                            <option value="7">7</option>
                                            <option value="8">8</option>
                                        </select>
                                    </label>
                                </div>
                            </div>

                            <div class="tour-step-actions">
                                <button class="tour-step-back" data-tour-back="calendar" type="button">Back</button>
                                <button class="tour-step-next" data-tour-next="contact" type="button">Continue</button>
                            </div>
                        </section>
                    </div>

                    <div class="contact-fields" data-tour-step="contact">
                        <section class="tour-step-card">
                            <div class="tour-step-top">
                                <div>
                                    <div class="tour-step-kicker">Step 3</div>
                                    <div class="tour-step-title">Your contact details</div>
                                </div>
                                <span class="tour-step-index">3</span>
                            </div>
                        </section>
                        <label class="field">
                            <span>Name</span>
                            <input name="customer_name" autocomplete="name" required>
                        </label>
                        <label class="field">
                            <span>Phone</span>
                            <input name="customer_phone" autocomplete="tel">
                        </label>
                        <label class="field full">
                            <span>Email</span>
                            <input name="customer_email" type="email" autocomplete="email">
                        </label>
                        <label class="field full" data-taxi-route-pickup>
                            <span>Pickup address</span>
                            <input name="pickup_address" placeholder="Hotel, villa or pickup point">
                        </label>
                        <div class="tour-step-actions field full">
                            <button class="tour-step-back" data-tour-back="calendar" type="button">Back</button>
                            <button class="tour-step-next" data-tour-next="summary" type="button">Continue</button>
                        </div>
                    </div>

                    <div class="type-fields" data-request-fields="hotel">
                        <label class="field">
                            <span>Check-in</span>
                            <input name="check_in_date" type="date">
                        </label>
                        <label class="field">
                            <span>Check-out</span>
                            <input name="check_out_date" type="date">
                        </label>
                        <label class="field">
                            <span>Guests</span>
                            <input name="guests" type="number" min="1" max="30" value="2">
                        </label>
                        <label class="field">
                            <span>Rooms</span>
                            <input name="rooms" type="number" min="1" max="12" value="1">
                        </label>
                    </div>

                    <div class="type-fields" data-request-fields="restaurant">
                        {{-- Day picker --}}
                        <div class="transfer-day-grid" id="restaurantDayGrid" aria-label="Reservation day">
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-restaurant-day type="button" aria-pressed="false"><span></span><span></span></button>
                        </div>

                        {{-- Time picker --}}
                        <div class="transfer-time-panel" id="restaurantTimePanel">
                            <span class="transfer-time-label">Reservation time</span>
                            <div class="transfer-time-grid" id="restaurantTimeGrid" aria-label="Reservation time"></div>
                        </div>

                        {{-- Summary chip (click to reset) --}}
                        <div class="transfer-date-summary" id="restaurantDateSummary" role="button" tabindex="0" title="Click to change">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="restaurantDateSummaryText"></span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:auto;opacity:.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </div>

                        <input name="reservation_date" id="restaurantDateInput" type="hidden">
                        <input name="reservation_time" id="restaurantTimeInput" type="hidden">

                        <label class="field full">
                            <span>Guests</span>
                            <div class="guest-selector">
                                <button type="button" class="guest-btn guest-minus" data-guest-action="minus">−</button>
                                <input name="guests" type="number" min="1" max="30" value="2" readonly>
                                <button type="button" class="guest-btn guest-plus" data-guest-action="plus">+</button>
                            </div>
                        </label>
                    </div>

                    <div class="type-fields" data-request-fields="taxi">
                        <label class="field">
                            <span>Pickup date and time</span>
                            <input name="pickup_date_time" type="datetime-local">
                        </label>
                        <label class="field">
                            <span>Passengers</span>
                            <input name="passengers" type="number" min="1" max="16" value="1">
                        </label>
                        <label class="field">
                            <span>Pickup address</span>
                            <input name="pickup_address">
                        </label>
                        <label class="field">
                            <span>Dropoff address</span>
                            <input name="dropoff_address">
                        </label>
                    </div>

                    <div class="type-fields" data-request-fields="transfer">
                        {{-- Day picker --}}
                        <div class="transfer-day-grid" id="transferDayGrid" aria-label="Pickup day">
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                        </div>

                        {{-- Time picker --}}
                        <div class="transfer-time-panel" id="transferTimePanel">
                            <span class="transfer-time-label">Pickup time</span>
                            <div class="transfer-time-grid" id="transferTimeGrid" aria-label="Pickup time"></div>
                        </div>

                        {{-- Summary chip (click to reset) --}}
                        <div class="transfer-date-summary" id="transferDateSummary" role="button" tabindex="0" title="Click to change">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="transferDateSummaryText"></span>
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:auto;opacity:.5"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </div>

                        <input name="pickup_date_time" id="transferDateTimeInput" type="hidden">

                        <label class="field">
                            <span>Passengers</span>
                            <input name="passengers" type="number" min="1" max="16" value="2">
                        </label>
                        <label class="field">
                            <span>Pickup hotel or location</span>
                            <input name="pickup_address" id="transferPickupInput" list="transferLocationOptions" placeholder="Hotel Princesa Yaiza" autocomplete="off">
                        </label>
                        <label class="field">
                            <span>Dropoff hotel or location</span>
                            <input name="dropoff_address" id="transferDropoffInput" list="transferLocationOptions" placeholder="Hotel Beatriz Costa Teguise" autocomplete="off">
                        </label>

                        {{-- Mini-mapa embebido --}}
                        <div id="transferMiniMapWrap" style="display:none; grid-column: 1 / -1; margin-top: 4px;">
                            <div style="position: relative; border-radius: 16px; overflow: hidden; border: 1px solid rgba(21,19,15,.1); box-shadow: 0 8px 24px rgba(0,0,0,.08);">
                                <div id="transferMiniMap" style="height: 220px; width: 100%; background: #dfe7dd;"></div>
                                <div id="transferRouteInfo" style="display:none; position: absolute; z-index:10000; bottom: 0; left: 0; right: 0; background: rgba(255,255,255,.92); backdrop-filter: blur(8px); padding: 10px 14px; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px;">
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#087f5b; flex-shrink:0;"></span>
                                        <span id="transferPickupLabel" style="font-weight:600; color:#15130f; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                                    </div>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#716b61" stroke-width="2" style="flex-shrink:0;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#dc4a25; flex-shrink:0;"></span>
                                        <span id="transferDropoffLabel" style="font-weight:600; color:#15130f; max-width:160px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                                    </div>
                                    <span id="transferRouteMeta" style="color:#716b61; font-weight:500; flex-shrink:0;"></span>
                                    <span id="transferPriceLabel" style="display:none; margin-left:auto; flex-shrink:0; background:var(--transfer); color:#fff; font-weight:800; font-size:13px; border-radius:8px; padding:3px 10px; letter-spacing:-.01em;"></span>
                                    <button id="transferConfirmBtn" type="button" style="display:none; flex-shrink:0; background:#087f5b; color:#fff; font-weight:700; font-size:12px; border-radius:8px; padding:5px 14px; border:none; cursor:pointer; letter-spacing:-.01em; white-space:nowrap; align-items:center; gap:5px;">
                                        Confirmar &amp; pagar
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                                <div id="transferMiniMapLoading" style="display:none; position:absolute; inset:0; background:rgba(255,255,255,.7); align-items:center; justify-content:center; backdrop-filter:blur(4px);">
                                    <div style="display:flex;align-items:center;gap:8px;color:#087f5b;font-weight:600;font-size:13px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                                        Calculating route...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input name="base_price" type="hidden">
                        <datalist id="transferLocationOptions"></datalist>
                    </div>

                    <div class="summary-fields" data-tour-step="summary">
                        <section class="tour-step-card">
                            <div class="tour-step-top">
                                <div>
                                    <div class="tour-step-kicker">Step 4</div>
                                    <div class="tour-step-title">Summary & payment</div>
                                </div>
                                <span class="tour-step-index">4</span>
                            </div>
                        </section>
                        <label class="field full">
                            <span>Notes</span>
                            <textarea name="notes" placeholder="Arrival time, preferences or useful details"></textarea>
                        </label>

                        {{-- Transfer upsell: shown for tour_visit, restaurant, hotel --}}
                        <div id="tourTransferUpsell" style="display:none; grid-column: 1 / -1; border-top: 1px solid rgba(21,19,15,.08); padding-top: 14px; margin-top: 4px;">
                            <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:4px;">
                                <input type="checkbox" id="addTransferToggle" style="width:17px;height:17px;accent-color:#087f5b; flex-shrink:0;">
                                <span style="font-weight:600; font-size:13px; color:#15130f;">Add a Taxilanz taxi transfer</span>
                                <span style="font-size:12px; color:#716b61; font-weight:500;">10% package discount</span>
                            </label>
                            <div id="tourTransferFields" style="display:none; margin-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <label class="field">
                                    <span>Pickup (hotel / villa)</span>
                                    <input id="tourTransferPickup" list="transferLocationOptions" placeholder="Hotel Princesa Yaiza" autocomplete="off">
                                </label>
                                <label class="field">
                                    <span>Dropoff</span>
                                    <input id="tourTransferDropoff" value="Bodega La Geria" readonly style="background:#f5f5f5; color:#716b61;">
                                </label>
                                <label class="field">
                                    <span>Pickup time (before tour)</span>
                                    <select id="tourTransferPickupTime">
                                        <option value="">Select tour time first</option>
                                    </select>
                                </label>
                                <label class="field">
                                    <span>Passengers</span>
                                    <input id="tourTransferPassengers" type="number" min="1" max="16" value="1">
                                </label>
                                <div style="grid-column:1/-1; min-height:22px; font-size:12px; color:#716b61;" id="tourTransferPriceInfo"></div>
                            </div>
                        </div>

                        {{-- Price breakdown --}}
                        <div id="priceBreakdown" style="display:none; grid-column: 1 / -1; border-top: 1px solid rgba(21,19,15,.08); padding-top: 14px; margin-top: 4px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <span style="font-size:13px; color:#716b61;">Service subtotal</span>
                                <span id="serviceSubtotal" style="font-weight:600; font-size:14px; color:#15130f;">--</span>
                            </div>
                            <div id="transferPriceRow" style="display:none; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <span style="font-size:13px; color:#716b61;">Transfer</span>
                                <span id="transferPriceDisplay" style="font-weight:600; font-size:14px; color:#15130f;">--</span>
                            </div>
                            <div id="discountRow" style="display:none; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <span style="font-size:13px; color:#087f5b;">Package discount (10%)</span>
                                <span id="discountDisplay" style="font-weight:600; font-size:14px; color:#087f5b;">--</span>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px solid rgba(21,19,15,.08);">
                                <span style="font-weight:700; font-size:15px; color:#15130f;">Total</span>
                                <span id="totalPriceDisplay" style="font-weight:800; font-size:18px; color:#087f5b;">--</span>
                            </div>
                        </div>

                        <div class="tour-step-actions field full">
                            <button class="tour-step-back" data-tour-back="contact" type="button">Back</button>
                        </div>
                    </div>
                </div>

                <div class="request-footer">
                    <p id="requestStatus" class="request-status">Phone or email is enough for the manager to reply.</p>
                    <button id="requestSubmit" class="primary-action request-submit" type="submit">Send request</button>
                </div>
            </form>
        </div>
    </section>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const typeConfig = {
            hotel: {
                color: '#2563eb',
                fallback: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"/><path d="M9 21v-6h6v6"/><path d="M9 9h.01"/><path d="M15 9h.01"/><path d="M9 12h.01"/><path d="M15 12h.01"/>',
            },
            restaurant: {
                color: '#dc4a25',
                fallback: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M7 2v20"/><path d="M11 2v8a4 4 0 0 1-8 0V2"/><path d="M17 2v20"/><path d="M17 2c2.2 1.4 3.5 4.2 3.5 7.5S19.2 15.6 17 17"/>',
            },
            taxi: {
                color: '#141414',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M5 17h14l-1.4-5.4A3 3 0 0 0 14.7 9H9.3a3 3 0 0 0-2.9 2.6L5 17Z"/><path d="M7 17v2"/><path d="M17 17v2"/><path d="M9 9l1-3h4l1 3"/><path d="M7.5 14h.01"/><path d="M16.5 14h.01"/>',
            },
            tour_visit: {
                color: '#7c3aed',
                fallback: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
            },
            taxi_route: {
                color: '#0f766e',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M6 19V5"/><path d="M18 19V5"/><path d="M6 8h12"/><path d="M6 16h12"/><path d="M9 5a3 3 0 0 1 6 0"/><path d="M9 19a3 3 0 0 0 6 0"/>',
            },
            transfer: {
                color: '#0891b2',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M4 17h16"/><path d="M6 17V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10"/><path d="M8 17v2"/><path d="M16 17v2"/><path d="M9 9h6"/><path d="M9 13h6"/>',
            },
        };

        const state = {
            places: [],
            activeTypes: new Set(),
            search: '',
            markers: new Map(),
            selectedId: null,
            requestPlace: null,
        };

        const map = L.map('exploreMap', {
            zoomControl: false,
            scrollWheelZoom: true,
        }).setView([28.9249, -13.5098], 6);

        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const layer = L.layerGroup().addTo(map);
        const resultsList = document.getElementById('resultsList');
        const resultsCount = document.getElementById('resultsCount');
        const detailDrawer = document.getElementById('detailDrawer');
        const mapTitle = document.getElementById('mapTitle');
        const mapSubtitle = document.getElementById('mapSubtitle');
        const requestModal = document.getElementById('requestModal');
        const requestForm = document.getElementById('requestForm');
        const requestStatus = document.getElementById('requestStatus');
        const requestSubmit = document.getElementById('requestSubmit');
        const tourSlotHint = document.getElementById('tourSlotHint');
        const tourSlotsPanel = document.getElementById('tourSlotsPanel');
        const tourSlotGrid = requestForm.querySelector('.tour-slot-grid');
        const tourDateInput = requestForm.querySelector('[name="tour_date"]');
        const tourScheduleInput = requestForm.querySelector('[name="tour_schedule"]');
        const tourCalendarNext = requestForm.querySelector('[data-tour-next="contact"]');
        const tourAdultsInput = requestForm.querySelector('[name="adults"]');
        const tourChildrenInput = requestForm.querySelector('[name="children"]');
        const tourAdultsValue = requestForm.querySelector('[data-stepper-value="adults"]');
        const tourChildrenSelect = requestForm.querySelector('[data-children-select]');
        const tourAttendeesSummary = document.getElementById('tourAttendeesSummary');
        let tourAvailabilityRequestId = 0;

        // ── Transfer mini-map ─────────────────────────────────────────────────
        let miniMap = null;
        let miniPickerMarker = null;
        let miniDropoffMarker = null;
        let miniRouteLayer = null;
        let miniMapInitialized = false;
        let transferRouteDebounce = null;

        const LANZAROTE_CENTER = [28.963, -13.648];

        function initMiniMap() {
            if (miniMapInitialized) { return; }
            miniMapInitialized = true;

            miniMap = L.map('transferMiniMap', {
                center: LANZAROTE_CENTER,
                zoom: 10,
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(miniMap);

            L.control.zoom({ position: 'topright' }).addTo(miniMap);
        }

        function showMiniMap() {
            const wrap = document.getElementById('transferMiniMapWrap');
            if (!wrap) { return; }
            wrap.style.display = '';
            initMiniMap();
            // Force Leaflet to recalculate size after display:none → visible
            setTimeout(() => miniMap?.invalidateSize(), 60);
        }

        function hideMiniMap() {
            const wrap = document.getElementById('transferMiniMapWrap');
            if (wrap) { wrap.style.display = 'none'; }
            clearMiniRouteElements();
        }

        function clearMiniRouteElements() {
            if (miniPickerMarker)  { miniMap?.removeLayer(miniPickerMarker);  miniPickerMarker  = null; }
            if (miniDropoffMarker) { miniMap?.removeLayer(miniDropoffMarker); miniDropoffMarker = null; }
            if (miniRouteLayer)    { miniMap?.removeLayer(miniRouteLayer);    miniRouteLayer    = null; }
            const info       = document.getElementById('transferRouteInfo');
            const loading    = document.getElementById('transferMiniMapLoading');
            const priceEl    = document.getElementById('transferPriceLabel');
            const confirmBtn = document.getElementById('transferConfirmBtn');
            if (info)       { info.style.display       = 'none'; }
            if (loading)    { loading.style.display    = 'none'; }
            if (priceEl)    { priceEl.style.display    = 'none'; priceEl.textContent = ''; }
            if (confirmBtn) { confirmBtn.style.display = 'none'; }
        }

        function makeMiniMarkerIcon(color) {
            return L.divIcon({
                className: '',
                html: `<div style="width:16px;height:16px;border-radius:50%;background:${color};border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,.35);"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
            });
        }

        async function updateMiniMapRoute() {
            const pickupVal  = document.getElementById('transferPickupInput')?.value?.trim();
            const dropoffVal = document.getElementById('transferDropoffInput')?.value?.trim();

            if (!pickupVal || !dropoffVal) { return; }

            showMiniMap();

            const loading = document.getElementById('transferMiniMapLoading');
            const info    = document.getElementById('transferRouteInfo');
            if (loading) { loading.style.display = 'flex'; }
            if (info)    { info.style.display    = 'none'; }

            clearMiniRouteElements();

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response  = await fetch('{{ route('maps.transfer-route') }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ pickup: pickupVal, dropoff: dropoffVal, mode: 'drive' }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    // Geocoding not available — just show the map centred on Lanzarote
                    miniMap.setView(LANZAROTE_CENTER, 10);
                    if (loading) { loading.style.display = 'none'; }
                    return;
                }

                // Markers
                const pLat = data.pickup?.lat;
                const pLon = data.pickup?.lon;
                const dLat = data.dropoff?.lat;
                const dLon = data.dropoff?.lon;

                if (pLat && pLon) {
                    miniPickerMarker = L.marker([pLat, pLon], { icon: makeMiniMarkerIcon('#087f5b') })
                        .bindTooltip('Pickup', { direction: 'top', offset: [0, -10] })
                        .addTo(miniMap);
                }

                if (dLat && dLon) {
                    miniDropoffMarker = L.marker([dLat, dLon], { icon: makeMiniMarkerIcon('#dc4a25') })
                        .bindTooltip('Dropoff', { direction: 'top', offset: [0, -10] })
                        .addTo(miniMap);
                }

                // Route polyline
                if (data.route?.features?.length) {
                    miniRouteLayer = L.geoJSON(data.route, {
                        style: { color: '#0891b2', weight: 4, opacity: .85 },
                    }).addTo(miniMap);
                    miniMap.fitBounds(miniRouteLayer.getBounds(), { padding: [28, 28] });
                } else if (pLat && dLat) {
                    // Fallback: straight line
                    miniRouteLayer = L.polyline([[pLat, pLon], [dLat, dLon]], {
                        color: '#0891b2', weight: 3, dashArray: '6 5', opacity: .7,
                    }).addTo(miniMap);
                    miniMap.fitBounds([[pLat, pLon], [dLat, dLon]], { padding: [36, 36] });
                }

                // Route info bar
                const distance = data.meta?.distance ?? data.route?.features?.[0]?.properties?.distance;
                const duration = data.meta?.time     ?? data.route?.features?.[0]?.properties?.time;
                const distKm   = distance ? `${(distance / 1000).toFixed(1)} km` : '';
                const durMin   = duration ? `~${Math.round(duration / 60)} min` : '';

                document.getElementById('transferPickupLabel').textContent  = data.pickup?.formatted  || pickupVal;
                document.getElementById('transferDropoffLabel').textContent = data.dropoff?.formatted || dropoffVal;
                document.getElementById('transferRouteMeta').textContent    = [distKm, durMin].filter(Boolean).join(' · ');

                if (info) { info.style.display = 'flex'; }

                // Price estimate (non-blocking)
                const priceEl      = document.getElementById('transferPriceLabel');
                const confirmBtn   = document.getElementById('transferConfirmBtn');
                const passengersEl = requestForm.querySelector('[name="passengers"]');
                if (priceEl && pickupVal && dropoffVal) {
                    priceEl.style.display = 'none';
                    if (confirmBtn) { confirmBtn.style.display = 'none'; }
                    estimateTransferPrice({
                        pickup_address:  pickupVal,
                        dropoff_address: dropoffVal,
                        passengers:      passengersEl?.value || '1',
                    }).then(estimate => {
                        if (estimate?.estimated_price) {
                            priceEl.textContent   = `${Number(estimate.estimated_price).toFixed(2)} €`;
                            priceEl.style.display = 'inline';

                            if (confirmBtn) {
                                confirmBtn.style.display = 'inline-flex';
                                confirmBtn.onclick = () => {
                                    const basePriceInput = requestForm.querySelector('[name="base_price"]');
                                    if (basePriceInput) { basePriceInput.value = String(estimate.estimated_price); }
                                    requestSubmit.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    requestSubmit.click();
                                };
                            }
                        }
                    }).catch(() => { /* silently ignore if not available */ });
                }

            } catch (err) {
                console.error('Mini map route error:', err);
            } finally {
                if (loading) { loading.style.display = 'none'; }
            }
        }

        function scheduleTransferRouteUpdate() {
            clearTimeout(transferRouteDebounce);
            transferRouteDebounce = setTimeout(updateMiniMapRoute, 700);
        }

        // ── Tour + Transfer upsell wiring ────────────────────────────────────
        let upsellPriceDebounce = null;

        function populateTransferPickupTimes(tourTime) {
            const select = document.getElementById('tourTransferPickupTime');
            if (!select || !tourTime) { return; }
            const [hour, minute] = tourTime.split(':').map(Number);
            const tourMinutes = hour * 60 + minute;
            const options = [
                { label: '30 min before', offset: 30 },
                { label: '1 hour before', offset: 60 },
                { label: '1.5 hours before', offset: 90 },
                { label: '2 hours before', offset: 120 },
            ];
            select.innerHTML = '<option value="">Select pickup time</option>';
            options.forEach(opt => {
                const pickupMinutes = tourMinutes - opt.offset;
                if (pickupMinutes >= 0) {
                    const h = Math.floor(pickupMinutes / 60);
                    const m = pickupMinutes % 60;
                    const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    select.innerHTML += `<option value="${timeStr}">${opt.label} (${timeStr})</option>`;
                }
            });
        }

        function refreshUpsellPrice() {
            const pickup     = document.getElementById('tourTransferPickup')?.value?.trim();
            const dropoff    = 'Bodega La Geria';
            const passengers = document.getElementById('tourTransferPassengers')?.value || '1';
            const info       = document.getElementById('tourTransferPriceInfo');
            if (!pickup || !info) { return; }
            info.textContent = 'Estimating transfer price…';
            estimateTransferPrice({ pickup_address: pickup, dropoff_address: dropoff, passengers })
                .then(est => {
                    if (est?.estimated_price) {
                        info.textContent = `Estimated transfer: ${Number(est.estimated_price).toFixed(2)} € · with 10% package discount`;
                    } else {
                        info.textContent = est?.error || 'Transfer price not available for this route.';
                    }
                })
                .catch(() => { info.textContent = ''; });
        }

        function scheduleUpsellPriceRefresh() {
            clearTimeout(upsellPriceDebounce);
            upsellPriceDebounce = setTimeout(refreshUpsellPrice, 700);
        }

        document.getElementById('addTransferToggle')?.addEventListener('change', (e) => {
            const fields = document.getElementById('tourTransferFields');
            if (fields) { fields.style.display = e.target.checked ? 'grid' : 'none'; }
            if (!e.target.checked) {
                document.getElementById('tourTransferPriceInfo').textContent = '';
            } else {
                // Preselect passengers from adults count
                const adultsInput = requestForm.querySelector('[name="adults"]');
                const passengersInput = document.getElementById('tourTransferPassengers');
                if (adultsInput && passengersInput) {
                    passengersInput.value = adultsInput.value || '1';
                }
                // Populate pickup times when tour time is already selected
                const tourSchedule = requestForm.querySelector('[name="tour_schedule"]')?.value;
                if (tourSchedule) { populateTransferPickupTimes(tourSchedule); }
            }
            updatePriceBreakdown();
        });

        document.addEventListener('input', (e) => {
            if (['tourTransferPickup', 'tourTransferPassengers'].includes(e.target.id)) {
                scheduleUpsellPriceRefresh();
            }
        });

        // When tour schedule changes, update pickup time options
        requestForm.addEventListener('change', (e) => {
            if (e.target.name === 'tour_schedule') {
                populateTransferPickupTimes(e.target.value);
            }
        });

        // Wire up transfer inputs via delegation (inputs exist in DOM already)
        requestForm.addEventListener('change', (e) => {
            if (e.target.id === 'transferPickupInput' || e.target.id === 'transferDropoffInput') {
                scheduleTransferRouteUpdate();
            }
        });
        // Also react on input (typing + selecting from datalist)
        requestForm.addEventListener('input', (e) => {
            if (e.target.id === 'transferPickupInput' || e.target.id === 'transferDropoffInput') {
                scheduleTransferRouteUpdate();
            }
        });

        function enableTransferMapMode()  { showMiniMap(); }
        function disableTransferMapMode() { hideMiniMap(); }

        // ── Transfer day/time picker ──────────────────────────────────────────
        let transferSelectedDate = null; // 'YYYY-MM-DD'
        let transferSelectedTime = null; // 'HH:MM'

        const TRANSFER_TIMES = (() => {
            const slots = [];
            for (let h = 5; h <= 23; h++) {
                slots.push(`${String(h).padStart(2, '0')}:00`);
                if (h < 23) { slots.push(`${String(h).padStart(2, '0')}:30`); }
            }
            return slots; // 05:00 … 23:00
        })();

        function initTransferDayGrid() {
            const weekday  = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
            const dayMonth = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });

            requestForm.querySelectorAll('[data-transfer-day]').forEach((btn, i) => {
                const d = new Date();
                d.setDate(d.getDate() + i);
                btn.dataset.transferDayValue = formatLocalDate(d);
                btn.querySelectorAll('span')[0].textContent = i === 0 ? 'Today' : i === 1 ? 'Tomorrow' : weekday.format(d);
                btn.querySelectorAll('span')[1].textContent = dayMonth.format(d);
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Build time grid
            const grid = document.getElementById('transferTimeGrid');
            if (grid && !grid.children.length) {
                grid.innerHTML = TRANSFER_TIMES.map(t =>
                    `<button class="transfer-time" data-transfer-time="${t}" type="button" aria-pressed="false">${t}</button>`
                ).join('');
                grid.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-transfer-time]');
                    if (btn) { selectTransferTime(btn); }
                });
            }
        }

        function selectTransferDay(btn) {
            requestForm.querySelectorAll('[data-transfer-day]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            transferSelectedDate = btn.dataset.transferDayValue;
            transferSelectedTime = null;

            // Reset time selection
            requestForm.querySelectorAll('[data-transfer-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            document.getElementById('transferTimePanel')?.classList.add('open');
            updateTransferDateTimeInput();
        }

        function selectTransferTime(btn) {
            requestForm.querySelectorAll('[data-transfer-time]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            transferSelectedTime = btn.dataset.transferTime;
            updateTransferDateTimeInput();

            // Scroll to make the active time visible
            btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function updateTransferDateTimeInput() {
            const input   = document.getElementById('transferDateTimeInput');
            const summary = document.getElementById('transferDateSummary');
            const sumText = document.getElementById('transferDateSummaryText');

            if (transferSelectedDate && transferSelectedTime) {
                const isoValue = `${transferSelectedDate}T${transferSelectedTime}`;
                if (input) { input.value = isoValue; }

                // Format for display
                const d      = new Date(isoValue);
                const label  = new Intl.DateTimeFormat(undefined, {
                    weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                }).format(d);

                if (sumText)  { sumText.textContent = label; }
                if (summary)  { summary.classList.add('visible'); }

                // Collapse day + time grids, show summary
                document.getElementById('transferDayGrid').style.display  = 'none';
                document.getElementById('transferTimePanel').classList.remove('open');
            } else {
                if (input)   { input.value = ''; }
                if (summary) { summary.classList.remove('visible'); }
            }
        }

        function resetTransferDatePicker() {
            transferSelectedDate = null;
            transferSelectedTime = null;
            const input   = document.getElementById('transferDateTimeInput');
            const summary = document.getElementById('transferDateSummary');
            const panel   = document.getElementById('transferTimePanel');
            const grid    = document.getElementById('transferDayGrid');
            if (input)   { input.value = ''; }
            if (summary) { summary.classList.remove('visible'); }
            if (panel)   { panel.classList.remove('open'); }
            if (grid)    { grid.style.display = ''; }
            requestForm.querySelectorAll('[data-transfer-day],[data-transfer-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
        }

        // ── Restaurant day/time picker ──────────────────────────────────────────
        let restaurantSelectedDate = null; // 'YYYY-MM-DD'
        let restaurantSelectedTime = null; // 'HH:MM'

        const RESTAURANT_TIMES = (() => {
            const slots = [];
            for (let h = 12; h <= 23; h++) {
                slots.push(`${String(h).padStart(2, '0')}:00`);
                if (h < 23) { slots.push(`${String(h).padStart(2, '0')}:30`); }
            }
            return slots; // 12:00 … 23:00
        })();

        function initRestaurantDayGrid() {
            const weekday  = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
            const dayMonth = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });

            requestForm.querySelectorAll('[data-restaurant-day]').forEach((btn, i) => {
                const d = new Date();
                d.setDate(d.getDate() + i);
                btn.dataset.restaurantDayValue = formatLocalDate(d);
                btn.querySelectorAll('span')[0].textContent = i === 0 ? 'Today' : i === 1 ? 'Tomorrow' : weekday.format(d);
                btn.querySelectorAll('span')[1].textContent = dayMonth.format(d);
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Build time grid
            const grid = document.getElementById('restaurantTimeGrid');
            if (grid && !grid.children.length) {
                grid.innerHTML = RESTAURANT_TIMES.map(t =>
                    `<button class="transfer-time" data-restaurant-time="${t}" type="button" aria-pressed="false">${t}</button>`
                ).join('');
                grid.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-restaurant-time]');
                    if (btn) { selectRestaurantTime(btn); }
                });
            }
        }

        function selectRestaurantDay(btn) {
            requestForm.querySelectorAll('[data-restaurant-day]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            restaurantSelectedDate = btn.dataset.restaurantDayValue;
            restaurantSelectedTime = null;

            // Reset time selection
            requestForm.querySelectorAll('[data-restaurant-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            document.getElementById('restaurantTimePanel')?.classList.add('open');
            updateRestaurantDateTimeInput();
        }

        function selectRestaurantTime(btn) {
            requestForm.querySelectorAll('[data-restaurant-time]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            restaurantSelectedTime = btn.dataset.restaurantTime;
            updateRestaurantDateTimeInput();

            // Scroll to make the active time visible
            btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function updateRestaurantDateTimeInput() {
            const dateInput = document.getElementById('restaurantDateInput');
            const timeInput = document.getElementById('restaurantTimeInput');
            const summary   = document.getElementById('restaurantDateSummary');
            const sumText   = document.getElementById('restaurantDateSummaryText');

            if (restaurantSelectedDate && restaurantSelectedTime) {
                if (dateInput) { dateInput.value = restaurantSelectedDate; }
                if (timeInput) { timeInput.value = restaurantSelectedTime; }

                // Format for display
                const d      = new Date(restaurantSelectedDate + 'T' + restaurantSelectedTime);
                const label  = new Intl.DateTimeFormat(undefined, {
                    weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                }).format(d);

                if (sumText)  { sumText.textContent = label; }
                if (summary)  { summary.classList.add('visible'); }

                // Collapse day + time grids, show summary
                document.getElementById('restaurantDayGrid').style.display  = 'none';
                document.getElementById('restaurantTimePanel').classList.remove('open');
            } else {
                if (dateInput) { dateInput.value = ''; }
                if (timeInput) { timeInput.value = ''; }
                if (summary)   { summary.classList.remove('visible'); }
            }
        }

        function resetRestaurantDatePicker() {
            restaurantSelectedDate = null;
            restaurantSelectedTime = null;
            const dateInput = document.getElementById('restaurantDateInput');
            const timeInput = document.getElementById('restaurantTimeInput');
            const summary   = document.getElementById('restaurantDateSummary');
            const panel     = document.getElementById('restaurantTimePanel');
            const grid      = document.getElementById('restaurantDayGrid');
            if (dateInput) { dateInput.value = ''; }
            if (timeInput) { timeInput.value = ''; }
            if (summary)   { summary.classList.remove('visible'); }
            if (panel)     { panel.classList.remove('open'); }
            if (grid)      { grid.style.display = ''; }
            requestForm.querySelectorAll('[data-restaurant-day],[data-restaurant-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
        }

        // ── Restaurant guest selector ───────────────────────────────────────────
        requestForm.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-guest-action]');
            if (!btn) return;

            const action = btn.dataset.guestAction;
            const input  = requestForm.querySelector('[name="guests"]');
            if (!input) return;

            let value = parseInt(input.value, 10) || 2;
            const min = parseInt(input.min, 10) || 1;
            const max = parseInt(input.max, 10) || 30;

            if (action === 'minus' && value > min) {
                value--;
            } else if (action === 'plus' && value < max) {
                value++;
            }

            input.value = value;
        });

        // Summary chip click → reset to show day grid again
        document.getElementById('transferDateSummary')?.addEventListener('click', resetTransferDatePicker);
        document.getElementById('transferDateSummary')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { resetTransferDatePicker(); }
        });

        document.getElementById('restaurantDateSummary')?.addEventListener('click', resetRestaurantDatePicker);
        document.getElementById('restaurantDateSummary')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { resetRestaurantDatePicker(); }
        });

        // Day buttons click delegation
        requestForm.addEventListener('click', (e) => {
            const dayBtn = e.target.closest('[data-transfer-day]');
            if (dayBtn) { selectTransferDay(dayBtn); }

            const restaurantDayBtn = e.target.closest('[data-restaurant-day]');
            if (restaurantDayBtn) { selectRestaurantDay(restaurantDayBtn); }
        });

        fetch('{{ route('public.explore.places') }}')
            .then(response => response.json())
            .then(payload => {
                state.places = payload.data || [];
                updateCounts(payload.meta?.types || {});
                hydrateTransferOptions();
                render();
                fitMap();
            })
            .catch(() => {
                resultsList.innerHTML = '<div class="empty-state">We could not load the public map data right now. Please try again in a moment.</div>';
                resultsCount.textContent = 'Unavailable';
            });

        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.filter;
                const checked = !state.activeTypes.has(type);

                if (checked) {
                    state.activeTypes.add(type);
                } else {
                    state.activeTypes.delete(type);
                }

                updateFilterState(button, checked);
                state.selectedId = null;
                render();
            });
        });

        document.getElementById('searchInput').addEventListener('input', event => {
            state.search = event.target.value.trim().toLowerCase();
            render();
        });

        document.getElementById('fitMapBtn').addEventListener('click', fitMap);
        document.getElementById('closeRequestModal').addEventListener('click', closeRequestModal);
        requestModal.addEventListener('click', event => {
            if (event.target === requestModal) {
                closeRequestModal();
            }
        });
        requestForm.addEventListener('submit', submitRequest);
        requestForm.querySelectorAll('[data-tour-next]').forEach(button => {
            button.addEventListener('click', async () => {
                const next = button.dataset.tourNext;
                if (next !== 'contact') {
                    setTourStep(next);
                    return;
                }

                const ok = await confirmTourSelectionAvailability();
                if (ok) {
                    setTourStep('contact');
                }
            });
        });
        requestForm.querySelectorAll('[data-tour-back]').forEach(button => {
            button.addEventListener('click', () => setTourStep(button.dataset.tourBack));
        });
        requestForm.querySelectorAll('[data-tour-day]').forEach(button => {
            button.addEventListener('click', () => selectTourDay(button));
        });
        tourSlotGrid?.addEventListener('click', event => {
            const button = event.target.closest('[data-tour-slot]');
            if (!button || button.disabled) {
                return;
            }

            selectTourSlot(button);
        });

        requestForm.querySelectorAll('[data-stepper-plus],[data-stepper-minus]').forEach(button => {
            button.addEventListener('click', () => handleStepper(button));
        });
        tourChildrenSelect?.addEventListener('change', () => {
            tourChildrenInput.value = String(parseInt(tourChildrenSelect.value || '0', 10) || 0);
            updateAttendeesSummary();
            handleParticipantsChanged();
        });

        document.getElementById('clearSelectionBtn').addEventListener('click', () => {
            state.selectedId = null;
            detailDrawer.classList.remove('open');
            renderCards(filteredPlaces());
            refreshMarkerStates();
        });

        document.getElementById('nearMeBtn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                mapSubtitle.textContent = 'Your browser does not support location detection.';
                return;
            }

            navigator.geolocation.getCurrentPosition(position => {
                const { latitude, longitude } = position.coords;
                L.circleMarker([latitude, longitude], {
                    radius: 8,
                    color: '#087f5b',
                    weight: 3,
                    fillColor: '#087f5b',
                    fillOpacity: .22,
                }).addTo(map);
                map.setView([latitude, longitude], 14, { animate: true });
                mapSubtitle.textContent = 'Centered on your location. Choose a marker nearby.';
            }, () => {
                mapSubtitle.textContent = 'Location permission was not available.';
            });
        });

        function render() {
            const places = filteredPlaces();
            const mappedPlaces = mappablePlaces(places);

            renderMarkers(mappedPlaces);
            renderCards(places);
            updateSummary(places, mappedPlaces);
        }

        function hydrateTransferOptions() {
            const datalist = document.getElementById('transferLocationOptions');
            if (!datalist) {
                return;
            }

            const names = Array.from(new Set(state.places
                .filter(place => ['hotel', 'restaurant'].includes(place.type))
                .map(place => place.name)
                .filter(Boolean)))
                .sort((a, b) => a.localeCompare(b));

            datalist.innerHTML = names.map(name => `<option value="${escapeHtml(name)}"></option>`).join('');
        }

        function filteredPlaces() {
            return state.places.filter(place => {
                const matchesType = state.activeTypes.has(place.type);
                const haystack = [place.name, place.label, place.address, place.description, ...Object.values(place.summary || {})]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                return matchesType && (!state.search || haystack.includes(state.search));
            });
        }

        function mappablePlaces(places) {
            return places.filter(place => place.has_coordinates && place.latitude !== null && place.longitude !== null);
        }

        function updateFilterState(button, checked) {
            button.classList.toggle('active', checked);
            button.setAttribute('aria-checked', checked ? 'true' : 'false');
        }

        function renderMarkers(places) {
            layer.clearLayers();
            state.markers.clear();

            places.forEach(place => {
                const marker = L.marker([place.latitude, place.longitude], {
                    icon: markerIcon(place),
                    riseOnHover: true,
                }).addTo(layer);

                marker.bindPopup(popupHtml(place), { closeButton: false, offset: [0, -10] });
                marker.on('click', () => selectPlace(place.id, true));
                state.markers.set(place.id, marker);
            });

            refreshMarkerStates();
        }

        function renderCards(places) {
            resultsCount.textContent = `${places.length} result${places.length === 1 ? '' : 's'}`;

            if (!places.length) {
                const msg = state.activeTypes.size === 0
                    ? 'Select at least one category above to explore places.'
                    : 'No places match the current filters. Try another category or search term.';
                resultsList.innerHTML = `<div class="empty-state">${msg}</div>`;
                return;
            }

            resultsList.innerHTML = places.map((place, index) => {
                const mapBadge = place.has_coordinates
                    ? '<span class="map-badge on-map">On map</span>'
                    : '<span class="map-badge">List only</span>';

                return `
                <button class="place-card ${state.selectedId === place.id ? 'selected' : ''} ${place.has_coordinates ? '' : 'unmapped'}" style="--place-color: ${typeConfig[place.type].color}; animation-delay: ${Math.min(index * 32, 240)}ms" type="button" data-place-id="${place.id}">
                    <img src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                    <span class="place-body">
                        <span class="place-meta">
                            <span class="type-pill">${escapeHtml(place.label)}</span>
                            <span style="display: flex; align-items: center; gap: 6px;">
                                ${mapBadge}
                                <span class="rating">${place.rating ? `★ ${place.rating}` : 'New'}</span>
                            </span>
                        </span>
                        <h2>${escapeHtml(place.name)}</h2>
                        <p>${escapeHtml(place.address || place.description || 'Public destination')}</p>
                        <span class="mini-facts">
                            ${Object.entries(place.summary || {}).slice(0, 3).map(([key, value]) => `<span>${escapeHtml(key)}: ${escapeHtml(value)}</span>`).join('')}
                        </span>
                    </span>
                </button>
            `;
            }).join('');

            resultsList.querySelectorAll('[data-place-id]').forEach(card => {
                card.addEventListener('click', () => selectPlace(card.dataset.placeId, true));
            });
        }

        function selectPlace(id, moveMap = false) {
            const place = state.places.find(item => item.id === id);
            if (!place) {
                return;
            }

            state.selectedId = id;
            renderCards(filteredPlaces());
            renderDetail(place);
            refreshMarkerStates();

            const marker = state.markers.get(id);
            if (marker) {
                marker.openPopup();
            }

            if (moveMap && place.has_coordinates) {
                map.flyTo([place.latitude, place.longitude], Math.max(map.getZoom(), 14), {
                    animate: true,
                    duration: .85,
                });
            } else if (moveMap) {
                mapSubtitle.textContent = `${place.name} is listed but does not have map coordinates yet.`;
            }
        }

        function renderDetail(place) {
            detailDrawer.innerHTML = `
                <img class="detail-image" src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                <div class="detail-content" style="--place-color: ${typeConfig[place.type].color}">
                    <span class="type-pill">${escapeHtml(place.label)}</span>
                    <h3>${escapeHtml(place.name)}</h3>
                    <p>${escapeHtml(place.description || place.address || 'Explore this public service on the map.')}</p>
                    <div class="mini-facts" style="margin-bottom: 14px">
                        ${Object.entries(place.summary || {}).slice(0, 3).map(([key, value]) => `<span>${escapeHtml(key)}: ${escapeHtml(value)}</span>`).join('')}
                    </div>
                    <div class="detail-actions">
                        <button class="primary-action request-open" type="button" data-request-place="${escapeHtml(place.id)}">Request booking</button>
                        ${place.phone ? `<a class="secondary-action" href="tel:${escapeHtml(place.phone)}">Call now</a>` : ''}
                        ${place.website ? `<a class="secondary-action" href="${escapeHtml(place.website)}" target="_blank" rel="noopener">Website</a>` : ''}
                        ${place.has_coordinates ? `<a class="secondary-action" href="https://www.google.com/maps/search/?api=1&query=${place.latitude},${place.longitude}" target="_blank" rel="noopener">Directions</a>` : '<span class="secondary-action">No map location yet</span>'}
                    </div>
                </div>
            `;
            detailDrawer.classList.add('open');
            detailDrawer.querySelector('.request-open')?.addEventListener('click', () => openRequestModal(place));
        }

        function openRequestModal(place) {
            state.requestPlace = place;
            const requestType = bookingType(place);
            requestForm.reset();
            clearMiniRouteElements();
            hideMiniMap();
            resetTransferDatePicker();
            requestForm.classList.toggle('tour-mode', requestType === 'tour');
            requestForm.dataset.tourCurrentStep = requestType === 'tour' ? 'calendar' : '';
            resetTourWizard();
            requestStatus.className = 'request-status';
            requestStatus.textContent = 'Phone or email is enough for the manager to reply.';
            requestSubmit.disabled = false;
            requestSubmit.textContent = 'Send request';

            document.getElementById('requestType').textContent = place.label;
            document.getElementById('requestType').style.color = typeConfig[place.type].color;
            document.getElementById('requestTitle').textContent = `Reserve ${place.name}`;
            const subtitleBase = `${place.address || place.label}. Your request goes to the direct manager first, then section manager or superadmin if needed.`;
            const sourceLine = place.source_label ? ` Source: ${place.source_label}.` : '';
            document.getElementById('requestSubtitle').textContent = subtitleBase + sourceLine;

            document.querySelectorAll('[data-request-fields]').forEach(group => {
                const active = group.dataset.requestFields === requestType;
                group.classList.toggle('active', active);
                group.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = !active;
                    if (active) {
                        input.required = requiredTypeFields(requestType).includes(input.name);
                    }
                });
            });
            requestForm.querySelectorAll('[data-taxi-route-pickup]').forEach(group => {
                const active = place.type === 'taxi_route';
                group.style.display = active ? '' : 'none';
                group.querySelectorAll('input').forEach(input => {
                    input.disabled = !active;
                    input.required = active;
                });
            });

            // Enable transfer map mode + init day picker
            if (place.type === 'transfer') {
                initTransferDayGrid();
                enableTransferMapMode();
            } else {
                disableTransferMapMode();
            }

            // Init restaurant day picker
            if (place.type === 'restaurant') {
                initRestaurantDayGrid();
            }

            // Show transfer upsell for tour_visit, restaurant, and hotel
            const upsell = document.getElementById('tourTransferUpsell');
            const addTransferToggle = document.getElementById('addTransferToggle');
            const dropoffInput = document.getElementById('tourTransferDropoff');
            if (upsell) {
                const showUpsell = ['tour_visit', 'restaurant', 'hotel'].includes(place.type);
                upsell.style.display = showUpsell ? '' : 'none';
                if (addTransferToggle) { addTransferToggle.checked = false; }
                document.getElementById('tourTransferFields').style.display = 'none';
                document.getElementById('tourTransferPriceInfo').textContent = '';

                // Set dropoff based on place type
                if (dropoffInput) {
                    if (place.type === 'tour_visit') {
                        dropoffInput.value = 'Bodega La Geria';
                    } else if (place.type === 'restaurant') {
                        dropoffInput.value = place.address || place.label || '';
                    } else if (place.type === 'hotel') {
                        dropoffInput.value = place.address || place.label || '';
                    } else {
                        dropoffInput.value = '';
                    }
                }
            }

            requestModal.classList.add('open');
            requestModal.setAttribute('aria-hidden', 'false');
            if (requestType === 'tour') {
                requestForm.querySelector('[data-tour-day]')?.focus();
            } else {
                requestForm.querySelector('[name="customer_name"]').focus();
            }
        }

        function closeRequestModal() {
            requestModal.classList.remove('open');
            requestModal.setAttribute('aria-hidden', 'true');
            state.requestPlace = null;
            disableTransferMapMode();
        }

        function resetTourWizard() {
            const dateFormatter = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
            const dayFormatter = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });

            requestForm.querySelectorAll('[data-tour-day]').forEach((button, index) => {
                const date = new Date();
                date.setDate(date.getDate() + index);
                const isoDate = formatLocalDate(date);
                const labels = button.querySelectorAll('span');

                button.dataset.tourDayValue = isoDate;
                labels[0].textContent = index === 0 ? 'Today' : index === 1 ? 'Tomorrow' : dateFormatter.format(date);
                labels[1].textContent = dayFormatter.format(date);
                button.classList.remove('active');
                button.setAttribute('aria-pressed', 'false');
            });

            if (tourSlotGrid) {
                tourSlotGrid.innerHTML = '';
            }

            if (tourDateInput) {
                tourDateInput.value = '';
            }

            if (tourScheduleInput) {
                tourScheduleInput.value = '';
            }

            tourSlotsPanel?.classList.remove('open');
            if (tourSlotHint) {
                tourSlotHint.textContent = 'Select a day to see available slots.';
            }
            syncTourSlots();
        }

        function formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function setTourStep(step) {
            requestForm.dataset.tourCurrentStep = step;

            if (step === 'attendees') {
                updateAttendeesSummary();
            }

            if (step === 'contact') {
                requestForm.querySelector('[name="customer_name"]')?.focus();
            }

            if (step === 'summary') {
                requestForm.querySelector('[name="notes"]')?.focus();
                updatePriceBreakdown();
            }
        }

        function updatePriceBreakdown() {
            const breakdown = document.getElementById('priceBreakdown');
            const serviceSubtotalEl = document.getElementById('serviceSubtotal');
            const transferPriceRow = document.getElementById('transferPriceRow');
            const transferPriceDisplay = document.getElementById('transferPriceDisplay');
            const discountRow = document.getElementById('discountRow');
            const discountDisplay = document.getElementById('discountDisplay');
            const totalDisplay = document.getElementById('totalPriceDisplay');

            if (!breakdown || !state.requestPlace) { return; }

            breakdown.style.display = 'grid';

            // Calculate service subtotal
            let serviceSubtotal = 0;
            const adults = parseInt(requestForm.querySelector('[name="adults"]')?.value || '1', 10);
            const unitPrice = state.requestPlace.unit_price || 0;
            serviceSubtotal = unitPrice * adults;

            serviceSubtotalEl.textContent = `${serviceSubtotal.toFixed(2)} €`;

            // Transfer price
            const addTransfer = document.getElementById('addTransferToggle')?.checked;
            let transferPrice = 0;
            if (addTransfer) {
                const priceInfo = document.getElementById('tourTransferPriceInfo')?.textContent;
                const match = priceInfo?.match(/Estimated transfer:\s*([\d.]+)/);
                if (match) {
                    transferPrice = parseFloat(match[1]) || 0;
                }
            }

            if (addTransfer && transferPrice > 0) {
                transferPriceRow.style.display = 'flex';
                transferPriceDisplay.textContent = `${transferPrice.toFixed(2)} €`;
            } else {
                transferPriceRow.style.display = 'none';
            }

            // Discount and total
            let total = serviceSubtotal + transferPrice;
            if (addTransfer) {
                const discount = total * 0.10;
                discountRow.style.display = 'flex';
                discountDisplay.textContent = `-${discount.toFixed(2)} €`;
                total -= discount;
            } else {
                discountRow.style.display = 'none';
            }

            totalDisplay.textContent = `${total.toFixed(2)} €`;
        }

        function selectTourDay(button) {
            requestForm.querySelectorAll('[data-tour-day]').forEach(dayButton => {
                dayButton.classList.toggle('active', dayButton === button);
                dayButton.setAttribute('aria-pressed', dayButton === button ? 'true' : 'false');
            });

            tourDateInput.value = button.dataset.tourDayValue;
            tourScheduleInput.value = '';

            if (tourSlotGrid) {
                tourSlotGrid.innerHTML = '';
            }

            tourSlotsPanel?.classList.add('open');
            if (tourSlotHint) {
                tourSlotHint.textContent = 'Loading slots...';
            }
            loadTourAvailability(button.dataset.tourDayValue);
            syncTourSlots();
        }

        function selectTourSlot(button) {
            tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                slotButton.classList.toggle('active', slotButton === button);
                slotButton.setAttribute('aria-pressed', slotButton === button ? 'true' : 'false');
            });

            tourScheduleInput.value = button.dataset.tourSlot;
            syncTourSlots();
        }

        function loadTourAvailability(dateValue) {
            if (!state.requestPlace || !tourSlotGrid) {
                return;
            }

            const requestId = ++tourAvailabilityRequestId;
            const url = new URL('{{ route('public.explore.availability') }}', window.location.origin);
            url.searchParams.set('type', state.requestPlace.type);
            url.searchParams.set('service_id', state.requestPlace.model_id);
            url.searchParams.set('date', dateValue);
            url.searchParams.set('participants', String(currentParticipants()));

            fetch(url.toString(), { headers: { 'Accept': 'application/json' } })
                .then(async response => {
                    const body = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = Object.values(body.errors || {})[0]?.[0];
                        throw new Error(firstError || body.message || 'Availability could not be loaded.');
                    }

                    return body;
                })
                .then(body => {
                    if (requestId !== tourAvailabilityRequestId) {
                        return;
                    }

                    const slots = body.data?.times || [];
                    const sourceLabel = body.data?.source?.source_label;

                    tourSlotGrid.innerHTML = '';

                    if (!Array.isArray(slots) || slots.length === 0) {
                        tourSlotHint.textContent = 'No slots available for the selected day.';
                        return;
                    }

                    tourSlotHint.textContent = sourceLabel ? `Slots from ${sourceLabel}.` : 'Available slots for the selected day.';

                    slots.forEach(slot => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'tour-slot';
                        button.dataset.tourSlot = slot.time;
                        button.textContent = slot.time;
                        button.disabled = slot.available === false;
                        button.setAttribute('aria-pressed', 'false');
                        tourSlotGrid.appendChild(button);
                    });
                })
                .catch(error => {
                    if (requestId !== tourAvailabilityRequestId) {
                        return;
                    }

                    tourSlotGrid.innerHTML = '';
                    tourSlotHint.textContent = error.message;
                });
        }

        function currentParticipants() {
            const adults = parseInt(tourAdultsInput?.value || '1', 10);
            const safeAdults = Number.isFinite(adults) && adults > 0 ? adults : 1;
            return safeAdults;
        }

        async function confirmTourSelectionAvailability() {
            if (!state.requestPlace || !tourDateInput?.value || !tourScheduleInput?.value) {
                return false;
            }

            if (tourSlotHint) {
                tourSlotHint.textContent = 'Checking availability...';
            }

            const url = new URL('{{ route('public.explore.availability') }}', window.location.origin);
            url.searchParams.set('type', state.requestPlace.type);
            url.searchParams.set('service_id', state.requestPlace.model_id);
            url.searchParams.set('date', tourDateInput.value);
            url.searchParams.set('participants', String(currentParticipants()));

            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstError = Object.values(body.errors || {})[0]?.[0];
                    throw new Error(firstError || body.message || 'Availability could not be checked.');
                }

                const slots = body.data?.times || [];
                const selected = slots.find(slot => slot.time === tourScheduleInput.value);

                if (!selected || selected.available === false) {
                    tourScheduleInput.value = '';
                    tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                        slotButton.classList.toggle('active', false);
                        slotButton.setAttribute('aria-pressed', 'false');
                    });
                    syncTourSlots();

                    if (tourSlotHint) {
                        tourSlotHint.textContent = 'That slot is no longer available. Please choose another time.';
                    }

                    return false;
                }

                if (tourSlotHint) {
                    tourSlotHint.textContent = 'Slot confirmed. Continue to add contact details.';
                }

                return true;
            } catch (error) {
                if (tourSlotHint) {
                    tourSlotHint.textContent = error.message || 'Availability could not be checked.';
                }
                return false;
            }
        }

        function syncTourSlots() {
            if (tourCalendarNext) {
                tourCalendarNext.disabled = !(tourDateInput?.value && tourScheduleInput?.value);
            }
        }

        function updateAttendeesSummary() {
            if (!tourAttendeesSummary) {
                return;
            }

            const adults = parseInt(tourAdultsInput?.value || '1', 10) || 1;
            const children = parseInt(tourChildrenInput?.value || '0', 10) || 0;
            tourAttendeesSummary.textContent = children > 0
                ? `Attendees: ${adults} · Children (free): ${children}`
                : `Attendees: ${adults}`;
        }

        function handleStepper(button) {
            const key = button.dataset.stepperPlus || button.dataset.stepperMinus;
            if (key !== 'adults') {
                return;
            }

            const current = parseInt(tourAdultsInput?.value || '1', 10) || 1;
            const delta = button.hasAttribute('data-stepper-plus') ? 1 : -1;
            const next = Math.max(1, Math.min(50, current + delta));
            tourAdultsInput.value = String(next);
            if (tourAdultsValue) {
                tourAdultsValue.textContent = String(next);
            }
            updateAttendeesSummary();
            handleParticipantsChanged();
        }

        function handleParticipantsChanged() {
            if (!tourDateInput?.value) {
                syncTourSlots();
                return;
            }

            tourScheduleInput.value = '';
            tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                slotButton.classList.remove('active');
                slotButton.setAttribute('aria-pressed', 'false');
            });

            if (tourSlotHint) {
                tourSlotHint.textContent = 'Loading slots...';
            }

            loadTourAvailability(tourDateInput.value);
            syncTourSlots();
        }

        function requiredTypeFields(type) {
            if (type === 'hotel') {
                return ['guests', 'rooms', 'check_in_date', 'check_out_date'];
            }

            if (type === 'restaurant') {
                return ['guests', 'reservation_date', 'reservation_time'];
            }

            if (type === 'tour') {
                return ['adults', 'tour_date', 'tour_schedule'];
            }

            if (type === 'transfer') {
                return ['passengers', 'pickup_date_time', 'pickup_address', 'dropoff_address'];
            }

            return ['passengers', 'pickup_date_time', 'pickup_address', 'dropoff_address'];
        }

        async function submitRequest(event) {
            event.preventDefault();

            if (!state.requestPlace) {
                return;
            }

            // ── Package (tour_visit + transfer) path ─────────────────────────
            const addTransferToggle = document.getElementById('addTransferToggle');
            if (state.requestPlace.type === 'tour_visit' && addTransferToggle?.checked) {
                const pickup      = document.getElementById('tourTransferPickup')?.value?.trim();
                const pickupTime  = document.getElementById('tourTransferPickupTime')?.value;
                const passengers  = document.getElementById('tourTransferPassengers')?.value || '1';
                const tourDate    = requestForm.querySelector('[name="tour_date"]')?.value;

                if (!pickup || !pickupTime || !tourDate) {
                    requestStatus.className = 'request-status error';
                    requestStatus.textContent = 'Please fill in pickup location and pickup time for the transfer.';
                    return;
                }

                const pickupAt = `${tourDate}T${pickupTime}:00`;

                requestStatus.className = 'request-status';
                requestStatus.textContent = 'Sending package request...';
                requestSubmit.disabled = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const formData  = new FormData(requestForm);
                const fd        = Object.fromEntries(formData.entries());

                const packagePayload = {
                    customer_name:    fd.customer_name  || '',
                    customer_email:   fd.customer_email || '',
                    customer_phone:   fd.customer_phone || '',
                    discount_percent: 10,
                    visit: {
                        tour_id:       state.requestPlace.model_id,
                        adults:        parseInt(fd.adults  || '1', 10),
                        children:      parseInt(fd.children || '0', 10),
                        tour_date:     fd.tour_date     || '',
                        tour_schedule: fd.tour_schedule || '',
                        unit_price:    state.requestPlace.unit_price ?? 0,
                    },
                    transfer: {
                        pickup:     pickup,
                        dropoff:    'Bodega La Geria',
                        pickup_at:  pickupAt,
                        passengers: parseInt(passengers, 10),
                    },
                };

                fetch(@json(route('public.explore.packages.store', [], false)), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(packagePayload),
                })
                    .then(async response => {
                        const body = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = Object.values(body.errors || {})[0]?.[0];
                            throw new Error(firstError || body.message || 'Package request could not be sent.');
                        }
                        return body;
                    })
                    .then(body => {
                        requestStatus.className = 'request-status success';
                        requestStatus.textContent = `Package ${body.data.reference} created.`;
                        requestSubmit.onclick = () => window.location.assign(body.data.pay_url);
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = `Pay ${body.data.amount_label}`;
                    })
                    .catch(error => {
                        requestStatus.className = 'request-status error';
                        requestStatus.textContent = error.message;
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = 'Send request';
                    });

                return;
            }

            // ── Standard single-item path ─────────────────────────────────────
            requestStatus.className = 'request-status';
            requestStatus.textContent = 'Sending request...';
            requestSubmit.disabled = true;

            const formData = new FormData(requestForm);
            const payload = Object.fromEntries(formData.entries());
            payload.type = ['taxi_route', 'transfer'].includes(state.requestPlace.type) ? state.requestPlace.type : bookingType(state.requestPlace);
            payload.service_id = state.requestPlace.model_id;

            if (state.requestPlace.type === 'taxi_route' && !payload.passengers && payload.adults) {
                payload.passengers = payload.adults;
            }

            if (state.requestPlace.type === 'transfer') {
                const estimate = await estimateTransferPrice(payload);
                if (!estimate?.ok || !estimate?.estimated_price) {
                    throw new Error(estimate?.error || 'We could not calculate this transfer price yet.');
                }

                payload.base_price = String(estimate.estimated_price);
                requestStatus.textContent = `Transfer fare ${Number(estimate.estimated_price).toFixed(2)}€ · ${estimate.pickup_tariff_zone || 'origin'} → ${estimate.dropoff_tariff_zone || 'destination'}`;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch(@json(route('public.explore.requests.store', [], false)), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then(async response => {
                    const body = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = Object.values(body.errors || {})[0]?.[0];
                        throw new Error(firstError || body.message || 'The request could not be sent.');
                    }

                    return body;
                })
                .then(body => {
                    const remoteStatus = body.data.remote_booking?.status;
                    const payUrl = body.data.payment?.pay_url;
                    const amountLabel = body.data.payment?.amount_label;
                    requestStatus.className = 'request-status success';
                    requestStatus.textContent = remoteStatus === 'created'
                        ? `Request ${body.data.reference} saved.`
                        : remoteStatus === 'failed'
                            ? `Request ${body.data.reference} sent locally. Remote booking failed and can be reviewed.`
                            : `Request ${body.data.reference} sent. Current status: pending.`;
                    requestSubmit.textContent = remoteStatus === 'created' ? 'Saved' : 'Sent';

                    if (remoteStatus === 'created' && payUrl) {
                        requestSubmit.onclick = () => window.location.assign(payUrl);
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = amountLabel ? `Pay ${amountLabel}` : 'Continue to payment';
                    }

                    const warnings = body.data.warnings || [];
                    if (warnings.length) {
                        requestStatus.className = 'request-status';
                        requestStatus.textContent = warnings[0];
                    }
                })
                .catch(error => {
                    requestStatus.className = 'request-status error';
                    requestStatus.textContent = error.message;
                    requestSubmit.disabled = false;
                    requestSubmit.textContent = 'Send request';
                });
        }

        async function estimateTransferPrice(payload) {
            const params = new URLSearchParams({
                pickup_location: payload.pickup_address || '',
                dropoff_location: payload.dropoff_address || '',
                passengers: payload.passengers || '1',
            });

            const response = await fetch(`${@json(route('public.explore.transfer-estimate', [], false))}?${params.toString()}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(body.message || 'Transfer price estimate failed.');
            }

            return body.data?.result || body.data;
        }

        function updateSummary(places, mappedPlaces = mappablePlaces(places)) {
            const noneSelected = state.activeTypes.size === 0;

            const labels = Array.from(state.activeTypes).map(type => {
                if (type === 'hotel')      return 'hotels';
                if (type === 'restaurant') return 'restaurants';
                if (type === 'taxi')       return 'taxi services';
                if (type === 'tour_visit') return 'visit tours';
                if (type === 'taxi_route') return 'taxi routes';
                if (type === 'transfer')   return 'transfers';
                return type;
            }).join(', ');

            mapTitle.textContent = noneSelected ? 'Select a category' : `${places.length} result${places.length === 1 ? '' : 's'}`;
            mapSubtitle.textContent = noneSelected
                ? 'Choose one or more filters above to explore Lanzarote.'
                : places.length
                    ? `${mappedPlaces.length} shown on the map · ${labels}.`
                    : 'No places match the current filters.';
        }

        function bookingType(place) {
            return place.booking_type || place.type;
        }

        function updateCounts(types) {
            Object.entries(types).forEach(([type, count]) => {
                const node = document.querySelector(`[data-count="${type}"]`);
                if (node) {
                    node.textContent = count;
                }
            });
        }

        function fitMap() {
            const places = mappablePlaces(filteredPlaces());
            if (!places.length) {
                mapSubtitle.textContent = 'No selected results have map coordinates yet.';
                return;
            }

            const bounds = L.latLngBounds(places.map(place => [place.latitude, place.longitude]));
            map.fitBounds(bounds, {
                paddingTopLeft: [40, 70],
                paddingBottomRight: [40, 70],
                maxZoom: 14,
                animate: true,
            });
        }

        function markerIcon(place) {
            const config = typeConfig[place.type];
            const active = state.selectedId === place.id ? ' active' : '';

            return L.divIcon({
                className: '',
                html: `<div class="marker-pin${active}" style="--pin-color: ${config.color}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${config.icon}</svg></div>`,
                iconSize: [42, 42],
                iconAnchor: [21, 38],
                popupAnchor: [0, -36],
            });
        }

        function refreshMarkerStates() {
            state.markers.forEach((marker, id) => {
                const place = state.places.find(item => item.id === id);
                if (place) {
                    marker.setIcon(markerIcon(place));
                }
            });
        }

        function popupHtml(place) {
            return `
                <div class="popup-card">
                    <img src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                    <div>
                        <strong>${escapeHtml(place.name)}</strong>
                        <span>${escapeHtml(place.address || place.label)}</span>
                    </div>
                </div>
            `;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character]);
        }
    </script>
</body>
</html>
