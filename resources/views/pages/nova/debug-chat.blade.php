<?php
use Laravel\Ai\Files\Image;
use Livewire\WithFileUploads;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Attributes\Layout;

new #[Title('NOVA Debug Chat')] #[Layout('layouts.front')] class extends Component
{

}
?>

<div
        x-data="novaDebugChat()"
        x-init="init()"
        class="flex h-[calc(100vh-64px)] gap-4 p-4"
    >

            <x-ui.button size="sm" @click="$wire.openCreate()">
                <x-lucide-plus class="size-4" />
                Nuevo Gasto
            </x-ui.button>
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
                    this.messages.push({
                        role: 'assistant',
                        text: 'NOVA Debug Chat activado. Envía "hola", "nvm" o cualquier comando de Copilot.',
                        time: new Date().toLocaleTimeString(),
                    });
                    this.$nextTick(() => this.scrollToBottom());
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
