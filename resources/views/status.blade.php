@php
    $allOperational = true;
    $hasDown = false;
    $hasWarning = false;
    $totalMonitors = isset($monitors) ? count($monitors) : 0;
    $downCount = 0;
    $warningCount = 0;
    if(isset($monitors)) {
        foreach($monitors as $monitor) {
            if($monitor['status'] === 'down') {
                $allOperational = false;
                $hasDown = true;
                $downCount++;
            } elseif($monitor['status'] === 'seems_down') {
                $allOperational = false;
                $hasWarning = true;
                $warningCount++;
            }
        }
    }
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Durum Paneli') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-gray-900 text-white py-6 shadow">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:justify-between md:items-center px-4">
            <div class="flex items-center">
                <img src="/logo.png" alt="Logo" class="h-16 w-auto mr-3" onerror="this.style.display='none'">
                <span class="text-2xl font-bold" style="display:none;" id="fallback-title">{{ config('app.name', 'Durum Paneli') }}</span>
            </div>
            <div class="mt-2 md:mt-0 text-right">
                <div class="font-semibold text-lg">Servis durumu</div>
                <div class="text-sm text-gray-300">Son güncelleme {{ now()->format('H:i:s') }} | Bir sonraki güncelleme <span id="counter">60</span> sn.</div>
            </div>
        </div>
    </header>
    <main class="max-w-5xl mx-auto px-4 mt-8">
        <div class="rounded-xl shadow-lg p-8 mb-8 flex items-center justify-between {{ $allOperational ? 'bg-green-50' : 'bg-yellow-50' }}">
            <div class="flex items-center">
                <span class="inline-block w-12 h-12 rounded-full mr-6 {{ $allOperational ? 'bg-green-400' : 'bg-yellow-400' }}"></span>
                <div>
                    <h2 class="text-2xl font-bold {{ $allOperational ? 'text-green-700' : 'text-yellow-700' }}">
                        @if($allOperational)
                            Tüm sistemler çalışıyor
                        @else
                            Bazı sistemlerde sorun var
                        @endif
                    </h2>
                    <div class="text-gray-500 mt-1">
                        @if($allOperational)
                            Tüm servisler sorunsuz çalışıyor.
                        @else
                            {{ $downCount > 0 ? $downCount.' servis çalışmıyor.' : '' }}
                            {{ $warningCount > 0 ? $warningCount.' serviste sorun olabilir.' : '' }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <section>
            <h2 class="text-xl font-semibold mb-4">Servisler</h2>
            <div class="bg-white rounded-xl shadow divide-y">
                @if(isset($monitors) && count($monitors) > 0)
                    @foreach($monitors as $monitor)
                        @php
                            $statusColor = match($monitor['status']) {
                                'up' => 'text-green-600',
                                'down' => 'text-red-600',
                                'seems_down' => 'text-yellow-600',
                                'paused' => 'text-gray-400',
                                default => 'text-gray-400',
                            };
                            $barColor = match($monitor['status']) {
                                'up' => 'bg-green-400',
                                'down' => 'bg-red-400',
                                'seems_down' => 'bg-yellow-400',
                                'paused' => 'bg-gray-300',
                                default => 'bg-gray-300',
                            };
                            $uptime = $monitor['uptime'] ?? null;
                        @endphp
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-6">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center mb-2">
                                    <span class="inline-block w-3 h-3 rounded-full mr-2 {{ $barColor }}"></span>
                                    <span class="font-semibold text-lg">{{ $monitor['friendly_name'] ?? 'İsimsiz Servis' }}</span>
                                    <span class="ml-3 text-sm text-gray-400">{{ $monitor['url'] ?? '' }}</span>
                                </div>
                                <div class="flex items-center mt-1">
                                    <div class="flex items-center text-sm mr-4">
                                        <i class="fas fa-percentage mr-1"></i>
                                        <span class="font-mono font-semibold {{ $statusColor }}">
                                            {{ $uptime ? number_format($uptime, 3) : 'N/A' }}%
                                        </span>
                                    </div>
                                    <span class="ml-2 px-2 py-1 rounded-full text-xs font-bold {{
                                        $monitor['status'] === 'up' ? 'bg-green-100 text-green-700' :
                                        ($monitor['status'] === 'down' ? 'bg-red-100 text-red-700' :
                                        ($monitor['status'] === 'seems_down' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'))
                                    }}">
                                        @if($monitor['status'] === 'up') Çalışıyor
                                        @elseif($monitor['status'] === 'down') Çalışmıyor
                                        @elseif($monitor['status'] === 'seems_down') Sorun olabilir
                                        @elseif($monitor['status'] === 'paused') Duraklatıldı
                                        @else Bilinmiyor
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4 md:mt-0 md:ml-6 text-xs text-gray-400 text-right">
                                @if(isset($monitor['last_check']))
                                    Son kontrol: {{ \Carbon\Carbon::createFromTimestamp($monitor['last_check'])->format('d.m.Y H:i:s') }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="p-6 text-center text-gray-400">Henüz izlenen sistem bulunmuyor.</div>
                @endif
            </div>
        </section>
    </main>
    <footer class="max-w-5xl mx-auto px-4 py-8 text-center text-gray-400 text-sm">
        Powered by <a href="https://uptimerobot.com" target="_blank" class="text-indigo-600 hover:underline">UptimeRobot</a>
    </footer>
    <script>
        // Otomatik yenileme ve sayaç
        let counter = 60;
        setInterval(function() {
            counter--;
            document.getElementById('counter').innerText = counter;
            if(counter <= 0) {
                location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
