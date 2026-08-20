<div
        x-data="novaDebugChat()"
        x-init="init()"
        class="flex h-[calc(100vh-64px)] gap-4 p-4"
    >

        {{-- LEFT: Conversation --}}
        <div class="flex flex-1 flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#111113]">
            <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-orange-600 text-white font-bold">D</div>
                    <div>
                        <h1 class="text-sm font-semibold text-white">NOVA Debug Chat</h1>
                        <p class="text-xs text-neutral-400" x-text="`Channel: ${channel} · User: ${user}`"></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="resetConversation()" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-neutral-300 hover:bg-white/5">Reset</button>
                    <button @click="exportConversation()" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-neutral-300 hover:bg-white/5">Export JSON</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4" x-ref="messages">
                <template x-for="(msg, index) in messages" :key="index">
                    <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                        <div :class="msg.role === 'user'
                            ? 'max-w-[80%] rounded-2xl rounded-tr-sm bg-orange-600 px-4 py-2.5 text-sm text-white'
                            : 'max-w-[80%] rounded-2xl rounded-tl-sm border border-white/10 bg-[#1b1b1f] px-4 py-2.5 text-sm text-neutral-100'"
                        >
                            <p x-html="msg.text"></p>

                            <template x-if="msg.choices && msg.choices.length && !msg.input_type">
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="(choice, cIdx) in msg.choices" :key="cIdx">
                                        <button
                                            @click="quickAsk(choiceValue(choice))"
                                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-neutral-200 transition hover:border-orange-500 hover:bg-orange-500/20 hover:text-orange-300"
                                            x-text="choiceLabel(choice)"
                                        ></button>
                                    </template>
                                </div>
                            </template>

                            <template x-if="msg.input_type === 'date'">
                                <div class="mt-3" x-html="buildCalendar()"></div>
                            </template>

                            <template x-if="msg.input_type === 'participants'">
                                <div class="mt-3" x-html="buildParticipantsSelector()"></div>
                            </template>

                            <template x-if="msg.input_type === 'service'">
                                <div class="mt-3 space-y-2">
                                    <template x-for="(choice, cIdx) in msg.choices" :key="cIdx">
                                        <button
                                            @click="quickAsk(choiceValue(choice))"
                                            class="w-full rounded-xl border border-white/10 bg-white/5 p-3 text-left transition hover:border-orange-500 hover:bg-orange-500/10"
                                        >
                                            <div class="text-sm font-semibold text-neutral-100" x-text="choiceLabel(choice)"></div>
                                            <div class="text-xs text-neutral-400" x-text="choiceDescription(choice)"></div>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <p class="mt-1 text-[10px] opacity-60" x-text="msg.time"></p>
                        </div>
                    </div>
                </template>
                <div x-show="loading" class="flex justify-start">
                    <div class="rounded-2xl rounded-tl-sm border border-white/10 bg-[#1b1b1f] px-4 py-2.5 text-sm text-neutral-400">Pensando...</div>
                </div>
            </div>

            <div class="border-t border-white/10 p-4">
                <form @submit.prevent="sendMessage()" class="flex gap-2">
                    <input
                        x-model="input"
                        x-ref="input"
                        type="text"
                        placeholder="Escribe un mensaje..."
                        class="flex-1 rounded-xl border border-white/10 bg-[#09090b] px-4 py-3 text-sm text-white placeholder-neutral-500 focus:border-orange-600 focus:outline-none"
                    >
                    <button type="submit" class="rounded-xl bg-orange-600 px-5 py-3 text-sm font-medium text-white hover:bg-orange-500">Enviar</button>
                </form>
            </div>
        </div>

        {{-- RIGHT: Inspector --}}
        <div class="flex w-[420px] flex-col gap-4 overflow-hidden">
            {{-- Pipeline & Timings --}}
            <div class="flex flex-1 flex-col overflow-hidden rounded-2xl border border-white/10 bg-[#111113]">
                <div class="border-b border-white/10 px-4 py-3">
                    <h2 class="text-sm font-semibold text-white">Inspector</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-4 space-y-5">
                    {{-- Timings --}}
                    <div x-show="lastResult && lastResult.timings">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Timings</h3>
                        <div class="space-y-1">
                            <template x-for="step in lastResult?.pipeline" :key="step.name">
                                <div class="flex justify-between text-xs">
                                    <span class="capitalize text-neutral-300" x-text="step.name.replace('_', ' ')"></span>
                                    <span class="text-neutral-400" x-text="`${step.duration_ms.toFixed(2)} ms`"></span>
                                </div>
                            </template>
                            <div class="flex justify-between border-t border-white/10 pt-1 text-xs font-semibold text-white">
                                <span>Total</span>
                                <span x-text="`${(lastResult?.timings?.total_ms ?? 0).toFixed(2)} ms`"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Intent --}}
                    <div x-show="lastResult?.intent">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Intent</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between"><span class="text-neutral-400">Name</span><span class="text-white" x-text="lastResult?.intent?.name"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Confidence</span><span class="text-white" x-text="lastResult?.intent?.confidence"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Target Capability</span><span class="text-white" x-text="lastResult?.intent?.target_capability ?? '-'"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Entities</span><span class="text-white" x-text="JSON.stringify(lastResult?.entities ?? {})"></span></div>
                        </div>
                    </div>

                    {{-- Conversation State --}}
                    <div x-show="lastResult?.conversation">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Conversation State</h3>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between"><span class="text-neutral-400">Capability</span><span class="text-white" x-text="lastResult?.conversation?.capability ?? '-'"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Operation</span><span class="text-white" x-text="lastResult?.conversation?.operation ?? '-'"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Current Step</span><span class="text-white" x-text="lastResult?.conversation?.current_step ?? '-'"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Status</span><span class="text-white" x-text="lastResult?.conversation?.status ?? '-'"></span></div>
                            <div class="flex justify-between"><span class="text-neutral-400">Data</span><span class="text-white truncate max-w-[200px]" x-text="JSON.stringify(lastResult?.conversation?.data ?? {})"></span></div>
                        </div>
                    </div>

                    {{-- Handler --}}
                    <div x-show="lastResult?.handler">
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Handler</h3>
                        <div class="text-xs text-neutral-300 break-all" x-text="lastResult?.handler"></div>
                    </div>

                    {{-- Debug switches --}}
                    <div>
                        <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-500">Debug Modules</h3>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <template x-for="mod in modules" :key="mod.key">
                                <label class="flex items-center gap-2 rounded-lg border border-white/10 px-2 py-1.5 text-neutral-300 cursor-pointer">
                                    <input type="checkbox" x-model="mod.active" class="accent-orange-600">
                                    <span x-text="mod.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Console --}}
            <div class="h-48 overflow-hidden rounded-2xl border border-white/10 bg-[#111113]">
                <div class="border-b border-white/10 px-4 py-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Console</h2>
                </div>
                <div class="h-full overflow-y-auto p-3 font-mono text-[10px] leading-relaxed text-neutral-400" x-ref="console">
                    <template x-for="(log, idx) in logs" :key="idx">
                        <div class="mb-1">
                            <span class="text-orange-500" x-text="`[${log.phase}]`"></span>
                            <span x-text="log.message"></span>
                            <span x-show="Object.keys(log.context ?? {}).length" class="text-neutral-500" x-text="JSON.stringify(log.context)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
 <script>
        function novaDebugChat() {
            return {
                channel: 'debug',
                user: 'debug-user',
                input: '',
                loading: false,
                messages: [],
                logs: [],
                lastResult: null,
                modules: [
                    { key: 'ai', label: 'IA', active: true },
                    { key: 'memory', label: 'Memory', active: true },
                    { key: 'context', label: 'Context', active: true },
                    { key: 'planner', label: 'Planner', active: true },
                    { key: 'voice', label: 'Voice', active: true },
                    { key: 'ocr', label: 'OCR', active: true },
                    { key: 'meta', label: 'Meta', active: false },
                    { key: 'whatsapp', label: 'WhatsApp', active: false },
                    { key: 'mcp', label: 'MCP', active: false },
                ],

                init() {
                    window.novaDebugChatApi = this;
                    this.messages.push({
                        role: 'assistant',
                        text: 'NOVA Debug Chat activado. Envía "hola", "nvm" o cualquier comando de Copilot.',
                        time: new Date().toLocaleTimeString(),
                    });
                    this.$nextTick(() => this.scrollToBottom());
                },

                quickAsk(text) {
                    this.input = text;
                    this.sendMessage();
                },

                choiceLabel(choice) {
                    if (choice === null || choice === undefined) return '';
                    if (typeof choice === 'string') return choice;
                    if (typeof choice === 'object') {
                        return choice.label || choice.text || choice.name || String(choice.value ?? choice.id ?? JSON.stringify(choice));
                    }
                    return String(choice);
                },

                choiceValue(choice) {
                    if (choice === null || choice === undefined) return '';
                    if (typeof choice === 'string') return choice;
                    if (typeof choice === 'object') {
                        return String(choice.value ?? choice.id ?? this.choiceLabel(choice));
                    }
                    return String(choice);
                },

                choiceDescription(choice) {
                    if (choice && typeof choice === 'object') {
                        return choice.description || choice.subtitle || '';
                    }
                    return '';
                },

                buildCalendar() {
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = now.getMonth();
                    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                    const dayNames = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const startDay = (firstDay.getDay() + 6) % 7;
                    const totalDays = lastDay.getDate();

                    let html = '<div class="rounded-xl border border-white/10 bg-[#09090b] p-3">';
                    html += '<div class="mb-2 text-center"><span class="text-sm font-bold text-white">' + monthNames[month] + '</span><span class="text-sm font-bold text-neutral-500 ml-1">' + year + '</span></div>';
                    html += '<div class="grid grid-cols-7 gap-1 text-center text-[10px] font-medium text-neutral-500 mb-1">';
                    dayNames.forEach(d => { html += '<div>' + d + '</div>'; });
                    html += '</div><div class="grid grid-cols-7 gap-1">';

                    for (let i = 0; i < startDay; i++) { html += '<div></div>'; }

                    for (let day = 1; day <= totalDays; day++) {
                        const value = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                        const isPast = new Date(year, month, day) < new Date(now.getFullYear(), now.getMonth(), now.getDate());
                        const isToday = day === now.getDate();

                        if (isPast) {
                            html += '<div class="aspect-square flex items-center justify-center text-xs text-neutral-600">' + day + '</div>';
                        } else {
                            html += '<button onclick="window.novaDebugChatApi.quickAsk(\'' + value + '\')" class="aspect-square flex items-center justify-center rounded-lg text-xs font-medium transition ' + (isToday ? 'bg-orange-600 text-white hover:bg-orange-500' : 'text-neutral-200 hover:bg-white/10 hover:text-orange-300') + '">' + day + '</button>';
                        }
                    }

                    html += '</div></div>';
                    return html;
                },

                buildParticipantsSelector() {
                    return '<div class="rounded-xl border border-white/10 bg-[#09090b] p-3">' +
                        '<div class="mb-3 text-sm font-semibold text-white">Participantes</div>' +
                        '<div class="flex items-center justify-between mb-3">' +
                        '<span class="text-xs text-neutral-400">Adultos</span>' +
                        '<div class="flex items-center gap-3">' +
                        '<button onclick="window.novaDebugChatApi.updateParticipants(\'adults\', -1)" class="grid h-7 w-7 place-items-center rounded-full border border-white/10 text-neutral-300 hover:bg-white/10">-</button>' +
                        '<span id="adults-value" class="w-4 text-center text-sm font-semibold text-white">2</span>' +
                        '<button onclick="window.novaDebugChatApi.updateParticipants(\'adults\', 1)" class="grid h-7 w-7 place-items-center rounded-full border border-white/10 text-neutral-300 hover:bg-white/10">+</button>' +
                        '</div></div>' +
                        '<div class="mb-4"><label class="block text-xs text-neutral-400 mb-1">Niños (&lt; 15)</label>' +
                        '<select id="children-select" class="w-full rounded-lg border border-white/10 bg-[#111113] px-3 py-2 text-sm text-white outline-none focus:border-orange-600">' +
                        '<option value="0" selected>0</option>' +
                        '<option value="1">1</option><option value="2">2</option><option value="3">3</option>' +
                        '<option value="4">4</option><option value="5">5</option><option value="6">6</option>' +
                        '<option value="7">7</option><option value="8">8</option></select></div>' +
                        '<button onclick="window.novaDebugChatApi.submitParticipants()" class="w-full rounded-lg bg-orange-600 py-2 text-sm font-semibold text-white hover:bg-orange-500">Confirmar</button>' +
                        '</div>';
                },

                updateParticipants(type, change) {
                    if (type !== 'adults') return;
                    let adults = parseInt(document.getElementById('adults-value').textContent) || 2;
                    adults = Math.max(1, adults + change);
                    document.getElementById('adults-value').textContent = adults;
                },

                submitParticipants() {
                    const adults = parseInt(document.getElementById('adults-value').textContent) || 2;
                    const children = parseInt(document.getElementById('children-select').value) || 0;
                    this.quickAsk(String(adults + children));
                },

                async sendMessage() {
                    const text = this.input.trim();
                    if (!text) return;

                    this.messages.push({ role: 'user', text, time: new Date().toLocaleTimeString() });
                    this.input = '';
                    this.loading = true;
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch(@js(route('nova.debug.chat.send')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            body: JSON.stringify({
                                message: text,
                                channel: this.channel,
                                user: this.user,
                                debug: true,
                            }),
                        });

                        const data = await response.json();
                        this.lastResult = data;
                        this.messages.push({
                            role: 'assistant',
                            text: data.reply || '(sin respuesta)',
                            time: new Date().toLocaleTimeString(),
                            choices: data.choices || [],
                            input_type: data.input_type || null,
                        });
                        this.logs = [...this.logs, ...(data.logs || [])];
                    } catch (error) {
                        this.messages.push({ role: 'assistant', text: 'Error: ' + error.message, time: new Date().toLocaleTimeString() });
                    } finally {
                        this.loading = false;
                        this.$nextTick(() => this.scrollToBottom());
                    }
                },

                scrollToBottom() {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                    const consoleEl = this.$refs.console;
                    if (consoleEl) consoleEl.scrollTop = consoleEl.scrollHeight;
                },

                resetConversation() {
                    this.messages = [];
                    this.logs = [];
                    this.lastResult = null;
                    this.init();
                },

                exportConversation() {
                    fetch(@js(route('nova.debug.chat.export')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ message: this.input || 'hola', channel: this.channel, user: this.user }),
                    })
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'conversation.json';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    });
                },
            };
        }
    </script>
