<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function index()
    {
        $apiKey = env('UPTIMEROBOT_API_KEY');
        
        if (empty($apiKey)) {
            return view('status', [
                'error' => 'UptimeRobot API anahtarı bulunamadı. Lütfen .env dosyasını kontrol edin.'
            ]);
        }

        try {
            $response = Http::asForm()->post('https://api.uptimerobot.com/v2/getMonitors', [
                'api_key' => $apiKey,
                'format' => 'json',
                'logs' => 1,
                'response_times' => 1,
                'response_times_average' => 1,
                'response_times_start_date' => strtotime('-1 day'),
                'response_times_end_date' => time(),
                'custom_uptime_ratios' => '30', // Son 30 günün uptime yüzdesi
            ]);

            $data = $response->json();

            if ($data['stat'] === 'ok') {
                $monitors = collect($data['monitors'])->map(function ($monitor) {
                    // Durum kodlarını anlaşılır hale getir
                    $status = match($monitor['status']) {
                        0 => 'paused',
                        1 => 'not_checked',
                        2 => 'up',
                        8 => 'seems_down',
                        9 => 'down',
                        default => 'unknown'
                    };

                    // Uptime yüzdesi (string olarak gelir, örn: '99.999')
                    $uptime = null;
                    if (isset($monitor['custom_uptime_ratio'])) {
                        // API'den gelen değer virgül ile ayrılmışsa (örn: '99.999,98.888') ilkini al
                        $uptime = explode(',', $monitor['custom_uptime_ratio'])[0];
                    }

                    // Uptime çubukları (son 30 log)
                    $uptime_bars = [];
                    if (isset($monitor['logs']) && is_array($monitor['logs'])) {
                        $logs = array_slice($monitor['logs'], 0, 30);
                        foreach ($logs as $log) {
                            $bar = match($log['type']) {
                                2 => 'up',        // up
                                9 => 'down',      // down
                                8 => 'seems_down',// seems_down
                                default => 'unknown',
                            };
                            $uptime_bars[] = $bar;
                        }
                    }

                    return [
                        'id' => $monitor['id'],
                        'friendly_name' => $monitor['friendly_name'],
                        'url' => $monitor['url'],
                        'status' => $status,
                        'response_times' => $monitor['response_times'] ?? [],
                        'last_check' => $monitor['last_check'] ?? null,
                        'uptime' => $uptime,
                        'uptime_bars' => $uptime_bars,
                    ];
                });

                return view('status', [
                    'monitors' => $monitors
                ]);
            } else {
                return view('status', [
                    'error' => 'API yanıtı başarısız: ' . ($data['error']['message'] ?? 'Bilinmeyen hata')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('UptimeRobot API Hatası: ' . $e->getMessage());
            return view('status', [
                'error' => 'API bağlantısı sırasında bir hata oluştu: ' . $e->getMessage()
            ]);
        }
    }
}
