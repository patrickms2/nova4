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
