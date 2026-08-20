<div id="websocket-status" class="{{ $floating ? 'websocket-status' : '' }} websocket-status-{{ $position }}"
    data-ws-url="{{ $wsUrl }}" data-reconnect-interval="{{ $reconnectInterval }}"
    data-show-traffic="{{ $showTraffic ? 'true' : 'false' }}">
    <div class="ws-indicator" onclick="toggleWebSocketDetails()">
        <div class="ws-status-dot" id="ws-status-dot"></div>
        <span class="ws-status-text" id="ws-status-text">{{ __('Connecting') }}...</span>
        <div class="ws-toggle">⚙️</div>
    </div>

    <div class="ws-details" id="ws-details" style="display: none;">
        <div class="ws-info">
            <div class="ws-info-item">
                <strong>URL:</strong> <span id="ws-url">{{ $wsUrl }}</span>
            </div>
            <div class="ws-info-item">
                <strong>{{ __('Status') }}:</strong> <span id="ws-detailed-status">{{ __('Disconnected') }}</span>
            </div>
            <div class="ws-info-item">
                <strong>{{ __('Last Connected') }}:</strong> <span id="ws-last-connected">{{ __('Never') }}</span>
            </div>
            <div class="ws-info-item">
                <strong>{{ __('Uptime') }}:</strong> <span id="ws-uptime">0s</span>
            </div>
        </div>

        @if ($showTraffic)
            <div class="ws-traffic">
                <h4>{{ __('Traffic Monitor') }}</h4>
                <div class="ws-traffic-stats">
                    <div class="ws-stat">
                        <span class="ws-stat-label">{{ __('Messages Sent') }}:</span>
                        <span class="ws-stat-value" id="ws-messages-sent">0</span>
                    </div>
                    <div class="ws-stat">
                        <span class="ws-stat-label">{{ __('Messages Received') }}:</span>
                        <span class="ws-stat-value" id="ws-messages-received">0</span>
                    </div>
                    <div class="ws-stat">
                        <span class="ws-stat-label">{{ __('Errors') }}:</span>
                        <span class="ws-stat-value" id="ws-errors">0</span>
                    </div>
                </div>
                <div class="ws-traffic-log" id="ws-traffic-log">
                    <div class="ws-log-item ws-log-info">{{ __('WebSocket monitor initialized') }}</div>
                </div>
            </div>
        @endif

        <div class="ws-actions">
            <button onclick="reconnectWebSocket()"
                class="ws-btn bg-primary-600 text-theme">{{ __('Reconnect') }}</button>
            <button onclick="clearTrafficLog()" class="ws-btn ws-btn-secondary">{{ __('Clear Log') }}</button>
        </div>
    </div>
</div>

<style>
    .websocket-status {
        position: fixed;
        z-index: 9999;
        font-size: 12px;
        max-width: 320px;
    }

    .websocket-status-top-right {
        top: 20px;
        right: 20px;
    }

    .websocket-status-top-left {
        top: 20px;
        left: 20px;
    }

    .websocket-status-bottom-right {
        bottom: 20px;
        right: 20px;
    }

    .websocket-status-bottom-left {
        bottom: 20px;
        left: 20px;
    }

    .websocket-status-center {
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }


    .ws-indicator {
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .ws-indicator:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
    }

    .ws-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ccc;
        animation: pulse 2s infinite;
    }


    .ws-status-dot.connected {
        background: #4CAF50;
        animation: none;
    }

    .ws-status-dot.disconnected {
        background: #f44336;
        animation: none;
    }

    .ws-status-dot.connecting {
        background: #ff9800;
        animation: pulse 1s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    .ws-status-text {
        font-weight: 500;
        color: #333;
    }

    .ws-toggle {
        margin-left: auto;
        cursor: pointer;
        opacity: 0.7;
        transition: opacity 0.2s;
    }

    .ws-toggle:hover {
        opacity: 1;
    }

    .ws-details {
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        margin-top: 8px;
        padding: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        backdrop-filter: blur(10px);
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ws-info-item {
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
    }

    .ws-traffic {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e0e0e0;
    }

    .ws-traffic h4 {
        margin: 0 0 12px 0;
        color: #333;
        font-size: 13px;
    }

    .ws-traffic-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 12px;
    }

    .ws-stat {
        background: #f5f5f5;
        padding: 6px 8px;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        font-size: 11px;
    }

    .ws-stat-value {
        font-weight: bold;
        color: #2196F3;
    }

    .ws-traffic-log {
        max-height: 120px;
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 4px;
        padding: 8px;
        margin-top: 8px;
        font-size: 11px;
    }

    .ws-log-item {
        padding: 2px 0;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
    }

    .ws-log-item:last-child {
        border-bottom: none;
    }

    .ws-log-sent {
        color: #4CAF50;
    }

    .ws-log-received {
        color: #2196F3;
    }

    .ws-log-error {
        color: #f44336;
    }

    .ws-log-info {
        color: #666;
    }

    .ws-actions {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 8px;
    }

    .ws-btn {
        flex: 1;
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 11px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .ws-btn-primary {
        background: #2196F3;
        color: white;
    }

    .ws-btn-primary:hover {
        background: #1976D2;
    }

    .ws-btn-secondary {
        background: #f5f5f5;
        color: #666;
    }

    .ws-btn-secondary:hover {
        background: #e0e0e0;
    }

    /* Scrollbar styling for traffic log */
    .ws-traffic-log::-webkit-scrollbar {
        width: 4px;
    }

    .ws-traffic-log::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 2px;
    }

    .ws-traffic-log::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 2px;
    }

    .ws-traffic-log::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>

<script>
    class WebSocketMonitor {
        constructor() {
            this.ws = null;
            this.wsUrl = document.getElementById('websocket-status').dataset.wsUrl;
            this.reconnectInterval = parseInt(document.getElementById('websocket-status').dataset
                .reconnectInterval);
            this.showTraffic = document.getElementById('websocket-status').dataset.showTraffic === 'true';
            this.reconnectTimer = null;
            this.uptimeTimer = null;
            this.connectionStartTime = null;

            this.stats = {
                messagesSent: 0,
                messagesReceived: 0,
                errors: 0
            };

            this.init();
        }

        init() {
            this.connect();
            this.updateUI();
        }

        connect() {
            try {
                this.updateStatus('connecting', 'Connecting...');
                this.ws = new WebSocket(this.wsUrl);

                this.ws.onopen = (event) => {
                    this.connectionStartTime = new Date();
                    this.updateStatus('connected', 'Connected');
                    this.startUptimeTimer();
                    this.logTraffic('Connected to Laravel Reverb', 'info');
                    document.getElementById('ws-last-connected').textContent = new Date().toLocaleString();

                    // Send connection event to Reverb
                    this.sendReverbConnectionEvent();

                    if (this.reconnectTimer) {
                        clearTimeout(this.reconnectTimer);
                        this.reconnectTimer = null;
                    }
                };

                this.ws.onmessage = (event) => {
                    this.stats.messagesReceived++;
                    this.updateStats();

                    try {
                        const data = JSON.parse(event.data);
                        const eventType = data.event || 'unknown';
                        const channel = data.channel || 'no-channel';
                        this.logTraffic(`📨 ${eventType} on ${channel}`, 'received');
                    } catch (e) {
                        this.logTraffic(
                            `📨 ${event.data.substring(0, 50)}${event.data.length > 50 ? '...' : ''}`,
                            'received');
                    }
                };

                this.ws.onclose = (event) => {
                    this.updateStatus('disconnected', 'Disconnected');
                    this.stopUptimeTimer();
                    this.logTraffic(`Connection closed (Code: ${event.code})`, 'error');

                    if (!event.wasClean) {
                        this.scheduleReconnect();
                    }
                };

                this.ws.onerror = (error) => {
                    this.stats.errors++;
                    this.updateStats();
                    this.updateStatus('disconnected', 'Error');
                    this.logTraffic('WebSocket error occurred', 'error');
                };

            } catch (error) {
                this.updateStatus('disconnected', 'Failed to connect');
                this.logTraffic(`Connection failed: ${error.message}`, 'error');
                this.scheduleReconnect();
            }
        }

        sendReverbConnectionEvent() {
            // Send a connection event that Reverb expects
            const connectionData = {
                event: 'pusher:connection_established'
            };

            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                try {
                    this.ws.send(JSON.stringify(connectionData));
                    this.logTraffic('📤 Connection established with Reverb', 'sent');
                } catch (error) {
                    this.logTraffic('Failed to send connection event', 'error');
                }
            }
        }

        disconnect() {
            if (this.ws) {
                this.ws.close(1000, 'Manual disconnect');
            }
            this.stopUptimeTimer();
            if (this.reconnectTimer) {
                clearTimeout(this.reconnectTimer);
                this.reconnectTimer = null;
            }
        }

        scheduleReconnect() {
            if (this.reconnectTimer) return;

            this.reconnectTimer = setTimeout(() => {
                this.logTraffic('Attempting to reconnect...', 'info');
                this.connect();
            }, this.reconnectInterval);
        }

        updateStatus(status, text) {
            const dot = document.getElementById('ws-status-dot');
            const statusText = document.getElementById('ws-status-text');
            const detailedStatus = document.getElementById('ws-detailed-status');

            dot.className = `ws-status-dot ${status}`;
            statusText.textContent = text;
            detailedStatus.textContent = text;
        }

        updateStats() {
            if (!this.showTraffic) return;

            document.getElementById('ws-messages-sent').textContent = this.stats.messagesSent;
            document.getElementById('ws-messages-received').textContent = this.stats.messagesReceived;
            document.getElementById('ws-errors').textContent = this.stats.errors;
        }

        logTraffic(message, type = 'info') {
            if (!this.showTraffic) return;

            const log = document.getElementById('ws-traffic-log');
            const item = document.createElement('div');
            item.className = `ws-log-item ws-log-${type}`;

            const timestamp = new Date().toLocaleTimeString();
            item.innerHTML = `
            <span>${message}</span>
            <span style="opacity: 0.7;">${timestamp}</span>
        `;

            log.appendChild(item);
            log.scrollTop = log.scrollHeight;

            // Keep only last 50 log entries
            while (log.children.length > 50) {
                log.removeChild(log.firstChild);
            }
        }

        startUptimeTimer() {
            this.uptimeTimer = setInterval(() => {
                if (this.connectionStartTime) {
                    const uptime = Math.floor((new Date() - this.connectionStartTime) / 1000);
                    document.getElementById('ws-uptime').textContent = this.formatUptime(uptime);
                }
            }, 1000);
        }

        stopUptimeTimer() {
            if (this.uptimeTimer) {
                clearInterval(this.uptimeTimer);
                this.uptimeTimer = null;
            }
            document.getElementById('ws-uptime').textContent = '0s';
        }

        formatUptime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m ${secs}s`;
            } else if (minutes > 0) {
                return `${minutes}m ${secs}s`;
            } else {
                return `${secs}s`;
            }
        }

        sendTestMessage() {
            if (this.ws && this.ws.readyState === WebSocket.OPEN) {
                // Send a ping event that Reverb understands
                const message = JSON.stringify({
                    event: 'pusher:ping',
                    data: {
                        timestamp: new Date().getTime(),
                        monitor: true
                    }
                });

                this.ws.send(message);
                this.stats.messagesSent++;
                this.updateStats();
                this.logTraffic('📤 Ping sent to Reverb', 'sent');
            } else {
                this.logTraffic('❌ Cannot send - WebSocket not connected', 'error');
            }
        }
    }

    // Global functions for component interaction
    let wsMonitor;

    function toggleWebSocketDetails() {
        const details = document.getElementById('ws-details');
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
    }

    function reconnectWebSocket() {
        if (wsMonitor) {
            wsMonitor.disconnect();
            wsMonitor.connect();
        }
    }

    function clearTrafficLog() {
        const log = document.getElementById('ws-traffic-log');
        log.innerHTML = '<div class="ws-log-item ws-log-info">Log cleared</div>';
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        wsMonitor = new WebSocketMonitor();
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (wsMonitor) {
            wsMonitor.disconnect();
        }
    });
</script>
