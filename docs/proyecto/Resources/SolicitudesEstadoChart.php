<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Http;

class SolicitudesEstadoChart extends ChartWidget
{
    protected ?string $heading = 'Solicitudes por Estado';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public function getPollingInterval(): ?string
    {
        return '15s';
    }

    public function getDescription(): ?string
    {
        return 'Distribución de solicitudes según su estado actual.';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $response = Http::timeout(10)->get(
            'https://www.taxisnorteysur.com/services/servicios.php',
            [
                'type' => 'read',
                'codestado' => 'undefined',
                'reservas' => 0,
                'options' => json_encode([
                    'action' => 'read',
                    'take' => 300,
                    'skip' => 0,
                    'page' => 1,
                    'pageSize' => 300,
                ]),
            ],
        );

        if (!$response->successful()) {
            return ['datasets' => [], 'labels' => []];
        }

        $servicios = $response->json('data') ?? [];

        $countByEstado = [];
        foreach ($servicios as $servicio) {
            $estado = trim((string)($servicio['nombreEstado'] ?? 'DESCONOCIDO'));
            $countByEstado[$estado] = ($countByEstado[$estado] ?? 0) + 1;
        }

        $colorMap = [
            'SOLICITADO' => ['bg' => 'rgba(245, 158, 11, 0.8)', 'border' => 'rgb(245, 158, 11)'],
            'TRAMITADO' => ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgb(34, 197, 94)'],
            'CANCELADO' => ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgb(239, 68, 68)'],
            'COMPLETADO' => ['bg' => 'rgba(59, 130, 246, 0.8)', 'border' => 'rgb(59, 130, 246)'],
        ];

        $defaultColor = ['bg' => 'rgba(156, 163, 175, 0.8)', 'border' => 'rgb(156, 163, 175)'];

        $labels = array_keys($countByEstado);
        $values = array_values($countByEstado);
        $bgColors = array_map(fn(string $e): string => ($colorMap[$e] ?? $defaultColor)['bg'], $labels);
        $borderColors = array_map(fn(string $e): string => ($colorMap[$e] ?? $defaultColor)['border'], $labels);

        return [
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $bgColors,
                    'borderColor' => $borderColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
