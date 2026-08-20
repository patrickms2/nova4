<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nova — Agente Principal</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="mx-auto flex min-h-screen max-w-5xl flex-col gap-6 p-4 md:flex-row md:items-start md:py-8">

  {{-- LEFT: Chat --}}
  <div class="flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl md:flex-1">

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-orange-700 px-5 py-5 text-white">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,.2),transparent_40%)]"></div>
      <div class="relative flex items-center gap-4">
        <div class="relative grid h-14 w-14 shrink-0 place-items-center rounded-full bg-white shadow-lg">
          <span class="text-2xl">🤖</span>
          <span class="absolute bottom-0.5 right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-400"></span>
        </div>
        <div class="min-w-0 flex-1">
          <h1 class="text-xl font-black">Nova — Agente Principal</h1>
          <p class="truncate text-sm text-white/80">
            {{ $activePrompt ? $activePrompt->title : 'Nova Orchestrator' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="debugToggle" class="sr-only peer">
            <div class="relative h-5 w-9 rounded-full bg-white/30 peer-checked:bg-emerald-400 transition-colors">
              <div class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></div>
            </div>
            <span class="text-xs font-medium text-white">Debug</span>
          </label>
          <button type="button" onclick="resetChat()"
             class="shrink-0 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white hover:bg-white/30 transition">
            Reiniciar
          </button>
          <a href="/admin/servers/1/edit" target="_blank"
             class="shrink-0 rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white hover:bg-white/30 transition">
            Admin →
          </a>
        </div>
      </div>
    </div>

    {{-- Agent context bar --}}
    @if($activePrompt)
    <div class="border-b border-slate-100 bg-amber-50 px-5 py-2.5 text-xs text-amber-700">
      <span class="font-semibold">Prompt activo:</span>
      <code class="ml-1">{{ $activePrompt->name }}</code>
      @if($activePrompt->description)
        <span class="ml-2 text-amber-600">— {{ $activePrompt->description }}</span>
      @endif
    </div>
    @endif

    {{-- Messages --}}
    <div id="chatBody" class="flex-1 space-y-4 overflow-y-auto bg-slate-50 px-5 py-5" style="height:420px">
      <div class="flex items-start gap-3">
        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white shadow text-lg">🤖</div>
        <div>
          <div class="max-w-sm rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-800 shadow">
            Hola 👋 Soy Nova, tu asistente en Lanzarote. ¿Qué quieres hacer hoy?
            <div class="mt-3 flex flex-wrap gap-2">
              <button onclick="quickAsk('Quiero reservar un taxi al aeropuerto')"
                class="rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 border border-orange-100 hover:bg-orange-100 transition">🚕 Taxi</button>
              <button onclick="quickAsk('Quiero reservar mesa en un restaurante')"
                class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 border border-blue-100 hover:bg-blue-100 transition">🍽️ Restaurante</button>
              <button onclick="quickAsk('Quiero visitar Bodega La Geria')"
                class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-100 hover:bg-emerald-100 transition">🍷 Bodega</button>
              <button onclick="quickAsk('visita bodega con taxi')"
                class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 border border-violet-100 hover:bg-violet-100 transition">🍷🚕 Visita + taxi</button>
              <button onclick="quickAsk('taxi con visita a bodega')"
                class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 border border-violet-100 hover:bg-violet-100 transition">🚕🍷 Taxi + visita</button>
 <button onclick="quickAsk('dime que restaurantes hay')"
                class="rounded-full bg-orange-50 px-3 py-1.5 text-xs font-semibold text-orange-700 border border-orange-100 hover:bg-orange-100 transition">🍽️ Restarantes</button>
              <button onclick="quickAsk('Opciones?')"
                class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 border border-violet-100 hover:bg-violet-100 transition">🗺️ Inicio</button>
              </div>
              
          </div>
          <p class="mt-1.5 text-xs text-slate-400">Nova · ahora</p>
        </div>
      </div>
    </div>

    {{-- Input --}}
    <div class="border-t border-slate-200 bg-white p-4">
      <div class="flex items-center gap-3">
        <div class="relative flex-1">
          <input id="chatInput" type="text"
            class="h-11 w-full rounded-full border border-slate-300 bg-slate-50 pl-5 pr-12 text-sm outline-none transition focus:border-orange-500 focus:ring-2 focus:ring-orange-100"
            placeholder="Escribe tu mensaje..."
          />
          <button
            type="button"
            id="voiceButton"
            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
            title="Dictar mensaje"
          >
            <svg id="micIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            <svg id="micOffIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
            </svg>
          </button>
        </div>
        <button onclick="sendMessage()"
          class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-orange-500 to-orange-700 text-white shadow-md shadow-orange-400/30 transition hover:scale-105 active:scale-95">
          ➤
        </button>
      </div>
      <p class="mt-3 text-center text-xs text-slate-400">
        ⚡ <span class="font-semibold text-orange-500">Nova Orchestrator</span>
        · Intent → Knowledge → MCP → Reply
      </p>
    </div>
  </div>

  {{-- RIGHT: Agent info panel --}}
  <div class="flex w-full flex-col gap-4 md:w-72">

    {{-- Prompts --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Prompts del agente</p>
      @forelse($allPrompts as $prompt)
        <a href="/admin/prompts/{{ $prompt->id }}/edit" target="_blank"
           class="mb-2 flex items-start gap-2 rounded-lg border px-3 py-2 text-xs hover:bg-slate-50 transition
                  {{ $activePrompt?->id === $prompt->id ? 'border-orange-300 bg-orange-50' : 'border-slate-100' }}">
          <span class="mt-0.5 text-amber-500">📄</span>
          <div class="min-w-0">
            <div class="truncate font-semibold text-slate-800">{{ $prompt->title }}</div>
            <code class="truncate text-slate-400">{{ $prompt->name }}</code>
          </div>
        </a>
      @empty
        <p class="text-xs text-slate-400">Sin prompts instalados.</p>
      @endforelse
      <a href="/admin/prompts" target="_blank"
         class="mt-1 block text-center text-xs text-orange-500 hover:underline">Ver todos →</a>
    </div>

    {{-- Last execution steps --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Último ciclo</p>
      <div id="stepsPanel" class="space-y-2 text-xs text-slate-500">
        <p class="italic">Envía un mensaje para ver los pasos.</p>
      </div>
    </div>

    {{-- Links --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Accesos directos</p>
      <div class="space-y-2 text-xs">
        <a href="/admin/server-chat?server=1" target="_blank"
           class="flex items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50 transition">
          💬 <span>Chat admin completo</span>
        </a>
        <a href="/admin/mcp-business-hub" target="_blank"
           class="flex items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50 transition">
          🗺️ <span>MCP Business Hub</span>
        </a>
        <a href="/admin/nova-ai-knowledge" target="_blank"
           class="flex items-center gap-2 rounded-lg border border-slate-100 px-3 py-2 hover:bg-slate-50 transition">
          🧠 <span>Editar knowledge</span>
        </a>
      </div>
    </div>

  </div>
</div>

<script>
  const chatBody = document.getElementById('chatBody');
  const chatInput = document.getElementById('chatInput');
  const stepsPanel = document.getElementById('stepsPanel');
  const voiceButton = document.getElementById('voiceButton');
  const micIcon = document.getElementById('micIcon');
  const micOffIcon = document.getElementById('micOffIcon');
  const initialChatHtml = chatBody.innerHTML;

  let recognition = null;
  let isListening = false;

  // Check for browser support
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

  if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'es-ES';

    recognition.onstart = function() {
      isListening = true;
      micIcon.classList.add('hidden');
      micOffIcon.classList.remove('hidden');
      voiceButton.classList.add('text-red-500', 'bg-red-50');
      voiceButton.classList.remove('text-slate-400');
    };

    recognition.onend = function() {
      isListening = false;
      micIcon.classList.remove('hidden');
      micOffIcon.classList.add('hidden');
      voiceButton.classList.remove('text-red-500', 'bg-red-50');
      voiceButton.classList.add('text-slate-400');
    };

    recognition.onresult = function(event) {
      const transcript = event.results[0][0].transcript;
      chatInput.value = transcript;
      chatInput.dispatchEvent(new Event('input'));
    };

    recognition.onerror = function(event) {
      console.error('Speech recognition error:', event.error);
      isListening = false;
      micIcon.classList.remove('hidden');
      micOffIcon.classList.add('hidden');
      voiceButton.classList.remove('text-red-500', 'bg-red-50');
      voiceButton.classList.add('text-slate-400');
    };

    function toggleVoiceRecording() {
      if (isListening) {
        recognition.stop();
      } else {
        recognition.start();
      }
    }

    voiceButton.addEventListener('click', toggleVoiceRecording);

    document.addEventListener('keydown', function(event) {
      if (event.repeat) {
        return;
      }

      if (event.altKey && event.key.toLowerCase() === 'z') {
        event.preventDefault();
        window.setTimeout(toggleVoiceRecording, 0);
      }
    });
  } else {
    voiceButton.style.display = 'none';
    console.log('Speech recognition not supported in this browser');
  }

  function addMessage(text, type = 'user', meta = null) {
    const wrapper = document.createElement('div');
    const time = new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    if (type === 'user') {
      wrapper.className = 'flex justify-end';
      wrapper.innerHTML = `
        <div class="max-w-xs">
          <div class="rounded-2xl rounded-tr-sm bg-gradient-to-br from-orange-500 to-orange-700 px-4 py-3 text-sm text-white shadow">
            ${escapeHtml(text)}
          </div>
          <p class="mt-1 text-right text-xs text-slate-400">${time} ✓</p>
        </div>`;
    } else {
      const label = meta?.tool ?? 'Nova';
      const choices = meta?.choices || null;
      const inputType = meta?.inputType || null;
      wrapper.className = 'flex items-start gap-3';
      
      let buttonsHtml = '';
      if (choices && Array.isArray(choices)) {
        buttonsHtml = '<div class="mt-3 flex flex-wrap gap-2">' +
          choices.map(choice => {
            const normalized = normalizeChoice(choice);
            return `<button onclick="quickAsk('${escapeHtml(normalized.value)}')"
              class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 border border-emerald-100 hover:bg-emerald-100 transition">
              ${escapeHtml(normalized.label)}
            </button>`;
          }).join('') +
          '</div>';
      } else if (inputType === 'date') {
        buttonsHtml = buildCalendar();
      } else if (inputType === 'participants') {
        buttonsHtml = buildParticipantsSelector();
      } else if (inputType === 'service') {
        buttonsHtml = buildServiceSelector(choices);
      }
      
      wrapper.innerHTML = `
        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white shadow text-lg">🤖</div>
        <div class="min-w-0 flex-1">
          <div class="text-xs font-semibold text-orange-600 mb-1">${escapeHtml(label)}</div>
          <div class="rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-800 shadow whitespace-pre-line break-words">
            ${escapeHtml(text)}
            ${buttonsHtml}
          </div>
          <p class="mt-1 text-xs text-slate-400">${time}</p>
        </div>`;
    }

    chatBody.appendChild(wrapper);
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function showTyping() {
    const id = 'typing-' + Date.now();
    const el = document.createElement('div');
    el.id = id;
    el.className = 'flex items-start gap-3';
    el.innerHTML = `
      <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white shadow text-lg">🤖</div>
      <div class="rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-400 shadow animate-pulse">
        Procesando…
      </div>`;
    chatBody.appendChild(el);
    chatBody.scrollTop = chatBody.scrollHeight;
    return id;
  }

  function renderSteps(data) {
    if (!data) { return; }
    const steps = [];
    if (data.conversation?.intent) {
      steps.push(`🎯 Intent: <strong>${data.conversation.intent}</strong>`);
    }
    if (data.knowledge?.length) {
      steps.push(`🧠 Knowledge: ${data.knowledge.length} fragmento(s) recuperado(s)`);
    }
    if (data.reservation_check !== undefined) {
      steps.push(`📅 Reservation check: ${data.reservation_check ? 'sí' : 'no aplica'}`);
    }
    if (data.nova_request_id) {
      steps.push(`🗂️ NovaRequest ID: <strong>${data.nova_request_id}</strong>`);
    }
    stepsPanel.innerHTML = steps.length
      ? steps.map(s => `<div class="rounded-lg bg-slate-50 px-2 py-1.5">${s}</div>`).join('')
      : '<p class="italic text-slate-400">Sin datos de ciclo.</p>';
  }

  async function botReply(message) {
    const typingId = showTyping();
    const debugEnabled = document.getElementById('debugToggle').checked;
    try {
      const res = await fetch('/api/nova/chat', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          message,
          channel: 'ai_bot',
          conversation_id: 'ai-bot-demo',
          user: {
            phone: '+340000001',
            name: 'Patrick',
            locale: 'es'
          },
          context: {
            source_url: window.location.href
          },
          debug: debugEnabled
        })
      });
      const data = await res.json();
      document.getElementById(typingId)?.remove();

      if (data.success && data.reply) {
        addMessage(data.reply, 'bot', { 
          tool: 'Nova Orchestrator',
          choices: data.choices || null,
          inputType: data.input_type || null
        });
        renderSteps(data);
      } else {
        addMessage('Lo siento, hubo un problema al procesar tu mensaje.', 'bot');
      }
    } catch (e) {
      document.getElementById(typingId)?.remove();
      addMessage('Error de conexión con el agente.', 'bot');
    }
  }

  function sendMessage() {
    const value = chatInput.value.trim();
    if (!value) return;
    addMessage(value, 'user');
    chatInput.value = '';
    botReply(value);
  }

  function quickAsk(text) {
    addMessage(text, 'user');
    botReply(text);
  }

  function buildCalendar() {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const dayNames = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = (firstDay.getDay() + 6) % 7; // Monday = 0
    const totalDays = lastDay.getDate();
    
    let calendar = `
      <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 text-center">
          <span class="text-lg font-bold text-slate-800">${monthNames[month]}</span>
          <span class="text-lg font-bold text-slate-400 ml-1">${year}</span>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-400 mb-2">
          ${dayNames.map(d => `<div>${d}</div>`).join('')}
        </div>
        <div class="grid grid-cols-7 gap-1">`;
    
    // Empty cells before first day
    for (let i = 0; i < startDay; i++) {
      calendar += '<div></div>';
    }
    
    // Day cells
    for (let day = 1; day <= totalDays; day++) {
      const date = new Date(year, month, day);
      const value = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const isPast = date < new Date(now.getFullYear(), now.getMonth(), now.getDate());
      const isToday = day === now.getDate();
      
      if (isPast) {
        calendar += `<div class="aspect-square flex items-center justify-center text-sm text-slate-300">${day}</div>`;
      } else {
        calendar += `
          <button onclick="quickAsk('${value}')"
            class="aspect-square flex items-center justify-center rounded-lg text-sm font-medium transition
              ${isToday ? 'bg-orange-500 text-white hover:bg-orange-600' : 'text-slate-700 hover:bg-orange-50 hover:text-orange-600'}">
            ${day}
          </button>`;
      }
    }
    
    calendar += '</div></div>';
    return calendar;
  }

  function buildParticipantsSelector() {
    return `
      <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
        <div class="tour-attendees-grid" aria-label="Attendees selection">
          <div class="tour-attendees-block mb-4">
            <div class="tour-attendees-label text-sm font-semibold text-slate-700 mb-2">Adults</div>
            <div class="tour-stepper flex items-center gap-3" role="group" aria-label="Adults">
              <button onclick="updateParticipants('adults', -1)" class="icon-button w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center hover:bg-slate-100 transition" type="button" aria-label="Decrease adults">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M5 12h14"></path></svg>
              </button>
              <div class="tour-stepper-value text-lg font-bold text-slate-800 w-8 text-center" id="adults-value">2</div>
              <button onclick="updateParticipants('adults', 1)" class="icon-button w-8 h-8 rounded-full border border-slate-300 flex items-center justify-center hover:bg-slate-100 transition" type="button" aria-label="Increase adults">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path d="M12 5v14"></path><path d="M5 12h14"></path></svg>
              </button>
            </div>
          </div>

          <div class="tour-attendees-block">
            <div class="tour-attendees-label text-sm font-semibold text-slate-700 mb-2">Children (free &lt; 15)</div>
            <select id="children-select" class="tour-children-select w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-orange-500" aria-label="Number of children">
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
          </div>
        </div>
        <button onclick="submitParticipants()" class="mt-4 w-full bg-orange-500 text-white font-semibold py-2 px-4 rounded-lg hover:bg-orange-600 transition">
          Confirmar
        </button>
      </div>
    `;
  }

  function buildServiceSelector(services) {
    if (!services || services.length === 0) return '';
    
    const flagMap = {
      'Visita Guiada ESP': 'https://lageriawp.test/wp-content/uploads/2025/10/es.svg',
      'Guided Tours ENG': 'https://lageriawp.test/wp-content/uploads/2025/10/gb.svg',
      'Visites guidées FRA': 'https://lageriawp.test/wp-content/uploads/2025/10/fr.svg',
      'Geführte Tour DEU': 'https://lageriawp.test/wp-content/uploads/2025/10/de.svg'
    };

    let html = '<div class="mt-3 space-y-3">';
    
    services.forEach(service => {
      const flagUrl = flagMap[service.name] || '';
      const price = service.price ? `${service.price}€` : '';
      
      html += `
        <button onclick="quickAsk('${service.id}')" 
          class="w-full rounded-xl border border-slate-200 bg-white p-4 text-left hover:border-orange-400 hover:shadow-md transition group">
          <div class="flex items-start gap-4">
            ${flagUrl ? `<div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
              <img src="${flagUrl}" alt="${service.name}" class="w-8 h-8 object-contain">
            </div>` : ''}
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-slate-800 group-hover:text-orange-600 transition">${service.name}</h3>
                ${price ? `<span class="text-lg font-bold text-orange-500">${price}</span>` : ''}
              </div>
              <p class="text-sm text-slate-600 line-clamp-2">${service.description || ''}</p>
              ${service.duration ? `<p class="text-xs text-slate-400 mt-1">Duración: ${service.duration} min</p>` : ''}
            </div>
          </div>
        </button>
      `;
    });
    
    html += '</div>';
    return html;
  }

  let adultsCount = 2;

  function updateParticipants(type, change) {
    if (type === 'adults') {
      adultsCount = Math.max(1, adultsCount + change);
      document.getElementById('adults-value').textContent = adultsCount;
    }
  }

  function submitParticipants() {
    const children = parseInt(document.getElementById('children-select').value);
    const total = adultsCount + children;
    quickAsk(total.toString());
  }

  async function resetChat() {
    if (!confirm('¿Eliminar esta conversación y empezar de nuevo?')) {
      return;
    }

    chatBody.innerHTML = initialChatHtml;
    chatInput.value = '';
    stepsPanel.innerHTML = '<p class="italic">Envía un mensaje para ver los pasos.</p>';

    try {
      await fetch('/api/nova/chat/reset', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
          conversation_id: 'ai-bot-demo',
          user: {
            phone: '+340000001'
          }
        })
      });
    } catch (e) {
      addMessage('No se pudo reiniciar el contexto del servidor, pero el chat visual se limpió.', 'bot');
    }
  }

  function normalizeChoice(choice) {
    if (choice === null || choice === undefined) {
      return { label: '', value: '' };
    }

    if (typeof choice === 'string') {
      try {
        const parsed = JSON.parse(choice);
        if (parsed && typeof parsed === 'object') {
          return normalizeChoice(parsed);
        }
      } catch (e) {
        // Not JSON, use string as both label and value
      }
      return { label: choice, value: choice };
    }

    if (typeof choice === 'object') {
      const label = choice.label || choice.text || choice.name || String(choice.value ?? choice.id ?? JSON.stringify(choice));
      const value = choice.value !== undefined ? String(choice.value) : String(choice.id ?? label);
      return { label, value };
    }

    const str = String(choice);
    return { label: str, value: str };
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  chatInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMessage(); });
</script>

</body>
</html>
