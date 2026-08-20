<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Taxilanz' }}</title>
    <meta name="description"
          content="{{ $metaDescription ?? 'Thin slice de TAXILANZ: taxi, recomendación, reserva y atribución.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --bg: #f7f7f1;
            --surface: rgba(255, 255, 255, 0.84);
            --surface-strong: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --line: rgba(15, 23, 42, 0.08);
            --accent: #0f766e;
            --accent-strong: #115e59;
            --accent-soft: rgba(15, 118, 110, 0.12);
            --warm: #f59e0b;
            --warm-soft: rgba(245, 158, 11, 0.12);
            --success: #dcfce7;
            --danger: #fef2f2;
            --danger-text: #b91c1c;
            --shadow: 0 24px 70px rgba(15, 23, 42, 0.10);
            --radius: 26px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top left, rgba(245, 158, 11, 0.16), transparent 22%),
            radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 24%),
            linear-gradient(180deg, #fcfcf8 0%, #f2f4ef 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            max-width: 100%;
            display: block;
        }

        code {
            padding: 0.12rem 0.4rem;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.06);
            font-size: 0.92em;
        }

        .shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(18px);
            background: rgba(247, 247, 241, 0.82);
            border-bottom: 1px solid var(--line);
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--accent), #14b8a6);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.42), 0 16px 32px rgba(15, 118, 110, 0.18);
        }

        .brand-copy small {
            display: block;
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 18px;
            color: var(--muted);
            font-size: 14px;
        }

        .nav-links a:hover {
            color: var(--text);
        }

        main {
            padding: 42px 0 80px;
        }

        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);
            gap: 24px;
            margin-bottom: 28px;
        }

        .card {
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .hero-copy {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 20px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.03em;
            background: var(--accent-soft);
            color: var(--accent);
        }

        h1, h2, h3 {
            margin: 0 0 12px;
            letter-spacing: -0.04em;
        }

        h1 {
            font-size: clamp(2.5rem, 5vw, 4.9rem);
            line-height: 0.93;
            max-width: 12ch;
        }

        h2 {
            font-size: clamp(1.55rem, 3vw, 2.4rem);
        }

        h3 {
            font-size: 1.12rem;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.68;
        }

        .muted {
            color: var(--muted);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .stat {
            padding: 18px;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.68);
        }

        .stat strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1.8rem;
            letter-spacing: -0.04em;
        }

        .stat span {
            color: var(--muted);
            font-size: 14px;
        }

        .orb-wrap {
            position: relative;
            overflow: hidden;
            min-height: 100%;
        }

        .orb-wrap::before,
        .orb-wrap::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
        }

        .orb-wrap::before {
            width: 130px;
            height: 130px;
            inset: auto auto 24px 16px;
            background: rgba(15, 118, 110, 0.18);
        }

        .orb-wrap::after {
            width: 170px;
            height: 170px;
            inset: 16px 18px auto auto;
            background: rgba(245, 158, 11, 0.16);
        }

        .stack {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 16px;
        }

        .mini-card {
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 22px;
            padding: 18px;
        }

        .mini-card small {
            display: block;
            margin-bottom: 8px;
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }

        .success {
            background: linear-gradient(180deg, rgba(220, 252, 231, 0.92), rgba(255, 255, 255, 0.86));
        }

        .split,
        .cards,
        .list,
        .offer-meta,
        .cta-row {
            display: grid;
            gap: 16px;
        }

        .split {
            grid-template-columns: minmax(0, 1.05fr) minmax(300px, 0.95fr);
        }

        .cards {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .list {
            margin-top: 18px;
        }

        .list-item {
            padding: 16px 18px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.7);
            color: var(--text);
        }

        .list-item p {
            margin-top: 6px;
        }

        .section {
            margin-top: 28px;
        }

        .proof-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .proof-card {
            display: grid;
            gap: 10px;
            align-content: start;
            min-height: 100%;
        }

        .storyboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .storyboard-card {
            display: grid;
            gap: 14px;
            align-content: start;
        }

        .storyboard-phone {
            display: grid;
            gap: 12px;
            min-height: 100%;
            padding: 16px;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.18), transparent 26%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(246, 249, 245, 0.95));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
        }

        .storyboard-step {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.10);
            color: var(--accent-strong);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .storyboard-step.is-warm {
            background: rgba(245, 158, 11, 0.14);
            color: #9a6700;
        }

        .storyboard-step.is-success {
            background: rgba(34, 197, 94, 0.12);
            color: #166534;
        }

        .storyboard-caption {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.65;
        }

        .storyboard-mini-list {
            display: grid;
            gap: 10px;
        }

        .storyboard-mini-item {
            display: grid;
            gap: 4px;
            padding: 12px 13px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.07);
            background: rgba(255, 255, 255, 0.86);
        }

        .storyboard-mini-item strong {
            font-size: 14px;
        }

        .storyboard-mini-item span {
            color: var(--muted);
            font-size: 12px;
        }

        .storyboard-progress {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 22px;
        }

        .storyboard-progress-step {
            display: grid;
            gap: 4px;
            padding: 13px 14px;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.72);
        }

        .storyboard-progress-step strong {
            font-size: 13px;
        }

        .storyboard-progress-step span {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .storyboard-progress-step.is-done {
            border-color: rgba(15, 118, 110, 0.16);
            background: rgba(15, 118, 110, 0.08);
        }

        .storyboard-progress-step.is-active {
            border-color: rgba(245, 158, 11, 0.22);
            background: rgba(245, 158, 11, 0.10);
        }


        .phone-shell {
            position: relative;
            padding: 14px;
            border-radius: 38px;
            background: linear-gradient(160deg, rgba(15, 23, 42, 0.96), rgba(15, 118, 110, 0.92));
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.22);
            max-width: 410px;
            margin-inline: auto;
        }

        .phone-shell::before {
            content: "";
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 34%;
            height: 28px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.92);
            z-index: 2;
        }

        .phone-shell::after {
            content: "";
            position: absolute;
            inset: 12px;
            border-radius: 30px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            pointer-events: none;
        }

        .phone-screen {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 20px 18px 18px;
            min-height: 100%;
            background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.20), transparent 28%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(244, 247, 243, 0.96));
            display: grid;
            gap: 14px;
        }

        .phone-topbar,
        .phone-list-row,
        .phone-action-top,
        .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .phone-topbar {
            font-size: 12px;
            font-weight: 800;
            color: var(--muted);
            margin-top: 6px;
        }

        .phone-dot-group {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .phone-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.24);
        }

        .phone-dot.is-live {
            background: linear-gradient(135deg, var(--accent), #14b8a6);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, 0.12);
        }

        .screen-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 118, 110, 0.10);
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .screen-title {
            margin: 0;
            font-size: clamp(1.8rem, 5vw, 2.5rem);
            line-height: 1;
            letter-spacing: -0.05em;
        }

        .screen-copy {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .route-card {
            display: grid;
            gap: 8px;
            padding: 16px;
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(15, 118, 110, 0.92), rgba(20, 184, 166, 0.78));
            color: #ecfeff;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18), 0 18px 34px rgba(15, 118, 110, 0.18);
        }

        .route-card small,
        .phone-action small,
        .phone-list-item small {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            opacity: 0.78;
        }

        .route-card strong {
            font-size: 1.1rem;
            line-height: 1.25;
            letter-spacing: -0.03em;
        }

        .route-card span {
            font-size: 14px;
            line-height: 1.45;
            opacity: 0.94;
        }

        .screen-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
        }

        .screen-kpi {
            padding: 12px;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.05);
            border: 1px solid rgba(15, 23, 42, 0.06);
            display: grid;
            gap: 4px;
        }

        .screen-kpi strong {
            font-size: 1.1rem;
            letter-spacing: -0.04em;
        }

        .screen-kpi span {
            color: var(--muted);
            font-size: 12px;
        }

        .phone-list {
            display: grid;
            gap: 10px;
        }

        .phone-list-item,
        .phone-action,
        .phone-empty {
            border-radius: 22px;
            padding: 14px 15px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .phone-list-item strong,
        .phone-action-title {
            font-size: 0.98rem;
            letter-spacing: -0.03em;
        }

        .phone-list-item span,
        .phone-action-top span {
            color: var(--accent-strong);
            font-size: 12px;
            font-weight: 800;
        }

        .phone-action {
            display: grid;
            gap: 10px;
            color: inherit;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .phone-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.10);
        }

        .phone-action-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .phone-action-meta span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.05);
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
        }

        .phone-action--accent {
            background: linear-gradient(180deg, rgba(236, 253, 245, 0.98), rgba(255, 255, 255, 0.96));
            border-color: rgba(15, 118, 110, 0.18);
        }

        .phone-empty {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .form-card {
            display: grid;
            align-content: start;
            gap: 16px;
        }

        .form-card form {
            gap: 12px;
        }

        .form-card label {
            padding: 12px;
            border-radius: 20px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.74);
        }

        .field-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .form-card input,
        .form-card select,
        .form-card textarea {
            padding: 13px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.96);
        }

        .form-card .btn {
            margin-top: 4px;
        }

        .form-note {
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(15, 23, 42, 0.04);
            border: 1px dashed rgba(15, 23, 42, 0.12);
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .section-head {
            margin-bottom: 16px;
            flex-wrap: wrap;
            align-items: end;
        }

        .section-head p {
            max-width: 42ch;
        }

        .proof-stat {
            font-size: clamp(2rem, 5vw, 2.8rem);
            letter-spacing: -0.05em;
            line-height: 1;
        }

        .inline-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--accent-strong);
            font-weight: 600;
        }

        .btn-secondary {
            background: rgba(15, 23, 42, 0.92);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
        }

        .offer-card {
            display: grid;
            gap: 16px;
            align-content: start;
        }

        .offer-visual {
            aspect-ratio: 16 / 10;
            border-radius: 22px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.95), rgba(245, 158, 11, 0.85));
        }

        .offer-visual::before,
        .offer-visual::after {
            content: "";
            position: absolute;
            border-radius: 999px;
        }

        .offer-visual::before {
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.16);
            top: -30px;
            right: -10px;
        }

        .offer-visual::after {
            width: 94px;
            height: 94px;
            background: rgba(15, 23, 42, 0.14);
            left: 18px;
            bottom: 18px;
        }

        .offer-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.06);
            color: var(--text);
            font-size: 12px;
            font-weight: 700;
        }

        form {
            display: grid;
            gap: 14px;
        }

        label {
            display: grid;
            gap: 8px;
            font-size: 14px;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.12);
            background: #ffffff;
            color: var(--text);
            font: inherit;
        }

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            border: 0;
            border-radius: 16px;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(135deg, var(--accent), #14b8a6);
            box-shadow: 0 16px 36px rgba(15, 118, 110, 0.24);
        }

        .btn-secondary {
            color: var(--text);
            background: rgba(15, 23, 42, 0.06);
            box-shadow: none;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .errors,
        .notice {
            padding: 16px 18px;
            border-radius: 18px;
            margin-top: 12px;
        }

        .errors {
            background: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.16);
            color: var(--danger-text);
        }

        .notice {
            background: rgba(15, 118, 110, 0.08);
            border: 1px solid rgba(15, 118, 110, 0.16);
            color: var(--accent-strong);
        }


        .hero-copy--compact {
            gap: 18px;
        }

        .compact-lead {
            max-width: 34ch;
            color: var(--text);
            font-size: 1rem;
            line-height: 1.55;
        }

        .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: rgba(255, 255, 255, 0.78);
            color: var(--text);
            font-size: 13px;
            font-weight: 700;
        }

        .story-rail {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .story-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.06);
            color: var(--muted);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .story-chip.is-done {
            background: rgba(15, 118, 110, 0.10);
            color: var(--accent-strong);
        }

        .story-chip.is-active {
            background: rgba(245, 158, 11, 0.14);
            color: #9a6700;
        }

        .decision-card,
        .decision-primary {
            display: grid;
            gap: 14px;
        }

        .decision-primary {
            padding: 18px;
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.07);
            background: rgba(255, 255, 255, 0.9);
        }

        .decision-primary--accent {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(240, 253, 250, 0.92));
            border-color: rgba(15, 118, 110, 0.14);
        }

        .decision-title {
            margin: 0;
            font-size: clamp(1.8rem, 4vw, 2.7rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
        }

        .inline-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .inline-actions .btn {
            min-width: 190px;
        }

        .icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 48px;
            padding: 12px 15px;
            border: 1px solid rgba(15, 23, 42, 0.10);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.86);
            color: var(--text);
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            position: relative;
        }

        .icon-button:hover {
            background: #ffffff;
            transform: translateY(-1px);
        }

        .icon-button[data-tooltip]:hover::after,
        .icon-button[data-tooltip]:focus-visible::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 50%;
            bottom: calc(100% + 8px);
            transform: translateX(-50%);
            padding: 8px 10px;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.94);
            color: #fff;
            white-space: nowrap;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.18);
        }

        .disclosure {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.76);
            overflow: hidden;
        }

        .disclosure--soft {
            background: rgba(15, 23, 42, 0.03);
        }

        .disclosure summary {
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 14px 16px;
            cursor: pointer;
            color: var(--text);
            font-size: 14px;
            font-weight: 800;
        }

        .disclosure summary::-webkit-details-marker {
            display: none;
        }

        .disclosure[open] summary {
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        }

        .disclosure-body {
            display: grid;
            gap: 10px;
            padding: 14px 16px 16px;
        }

        .accordion-list {
            margin: 0;
            padding-left: 18px;
            color: var(--muted);
            display: grid;
            gap: 8px;
        }

        .accordion-list strong {
            color: var(--text);
        }

        .phone-screen--focused {
            gap: 14px;
        }

        .recommendation-focus {
            display: grid;
            gap: 12px;
            padding: 16px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(15, 118, 110, 0.14);
            box-shadow: 0 18px 40px rgba(15, 118, 110, 0.10);
        }

        .recommendation-focus .btn {
            width: 100%;
        }

        .metric-strip {
            display: grid;
            gap: 12px;
        }

        .metric-strip--2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .metric-strip--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .metric-cell {
            display: grid;
            gap: 4px;
            padding: 14px;
            border-radius: 18px;
            background: rgba(15, 23, 42, 0.04);
            border: 1px solid rgba(15, 23, 42, 0.06);
        }

        .metric-cell strong {
            font-size: 1.2rem;
            letter-spacing: -0.04em;
        }

        .metric-cell span {
            color: var(--muted);
            font-size: 12px;
        }

        .section-head--compact {
            margin-bottom: 14px;
        }

        .cards--compact {
            gap: 16px;
        }

        .offer-card--compact {
            gap: 14px;
        }

        .app-modal {
            width: min(560px, calc(100% - 24px));
            border: 0;
            border-radius: 26px;
            padding: 0;
            background: var(--surface-strong);
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.24);
        }

        .app-modal::backdrop {
            background: rgba(15, 23, 42, 0.42);
            backdrop-filter: blur(4px);
        }

        .app-modal-card {
            display: grid;
            gap: 16px;
            padding: 24px;
        }

        .app-modal-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .app-modal-close {
            border: 0;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.08);
            color: var(--text);
            padding: 10px 12px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .footer-note {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 980px) {
            .hero,
            .split,
            .cards,
            .proof-grid,
            .storyboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .topbar-inner,
            .footer-note {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats,
            .grid-2,
            .screen-kpis,
            .storyboard-progress,
            .metric-strip--2,
            .metric-strip--3 {
                grid-template-columns: 1fr;
            }

            .phone-shell {
                max-width: none;
                width: 100%;
            }

            .phone-topbar,
            .phone-list-row,
            .phone-action-top,
            .section-head,
            .app-modal-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .inline-actions,
            .inline-actions--stack-mobile {
                flex-direction: column;
            }

            .inline-actions .btn,
            .icon-button {
                width: 100%;
            }

            main {
                padding-top: 28px;
            }

            .card {
                padding: 22px;
            }

            .nav-links {
                gap: 12px;
            }
        }
    </style>
    @livewireStyles
</head>
<body>
<div class="relative overflow-hidden pb-14">
    <div
        class="pointer-events-none absolute inset-x-0 top-[-12rem] h-[26rem] bg-[radial-gradient(circle_at_top,_rgba(15,118,110,0.16),_transparent_58%)]"></div>
    <header class="page-shell relative z-10 pt-5 sm:pt-7">
        <div class="surface-card flex items-center justify-between gap-4 px-5 py-4 sm:px-7">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-3">
                <span
                    class="flex h-11 w-11 items-center justify-center rounded-[18px] bg-[linear-gradient(135deg,#0f766e,#14b8a6)] text-lg font-black text-white shadow-[0_14px_26px_rgba(15,118,110,0.22)]">T</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-500">Taxi to booking</p>
                    <p class="text-lg font-semibold text-slate-900">Taxilanz</p>
                </div>
            </a>
            <nav class="flex items-center gap-2 text-sm text-slate-600">
                <a href="{{ route('home') }}" wire:navigate
                   class="hidden rounded-full px-4 py-2 transition hover:bg-white/70 sm:inline-flex">Flujo turista</a>
                <a href="/admin" class="secondary-button !px-4 !py-2.5 !text-sm">Panel</a>
            </nav>
        </div>
    </header>

    <main class="relative z-10 pt-6 sm:pt-8">
        <div x-data x-init="requestAnimationFrame(() => $el.classList.add('is-in'))" class="motion-page">
            {{ $slot }}
        </div>
    </main>
</div>

@livewireScripts
</body>
</html>
