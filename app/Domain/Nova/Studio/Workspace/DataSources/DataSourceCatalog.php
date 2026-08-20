<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\DataSources;

final readonly class DataSourceCatalog
{
    /**
     * Tools and data sources map to candidate connectors and providers.
     *
     * @return array<string, array{icon: string, name: string, connectors: array<int, string>, providers: array<int, string>}>
     */
    public function tools(): array
    {
        return [
            'excel' => ['icon' => '📊', 'name' => 'Excel', 'connectors' => [], 'providers' => []],
            'whatsapp' => ['icon' => '💬', 'name' => 'WhatsApp', 'connectors' => ['Conector de WhatsApp'], 'providers' => ['Meta']],
            'booking' => ['icon' => '🏨', 'name' => 'Booking', 'connectors' => ['Conector de reservas de hotel'], 'providers' => ['Motor hotelero']],
            'airbnb' => ['icon' => '🏠', 'name' => 'Airbnb', 'connectors' => ['Conector de reservas de hotel'], 'providers' => ['Motor hotelero']],
            'gmail' => ['icon' => '✉', 'name' => 'Gmail', 'connectors' => [], 'providers' => []],
            'stripe' => ['icon' => '💳', 'name' => 'Stripe', 'connectors' => ['Conector de pagos'], 'providers' => ['Stripe']],
            'wordpress' => ['icon' => '🌐', 'name' => 'WordPress', 'connectors' => [], 'providers' => []],
            'woocommerce' => ['icon' => '🛒', 'name' => 'WooCommerce', 'connectors' => [], 'providers' => []],
            'magento' => ['icon' => '🛍', 'name' => 'Magento', 'connectors' => [], 'providers' => []],
            'latepoint' => ['icon' => '📅', 'name' => 'LatePoint', 'connectors' => [], 'providers' => []],
            'google-calendar' => ['icon' => '📆', 'name' => 'Google Calendar', 'connectors' => ['Conector de calendario'], 'providers' => ['Calendario NOVA']],
            'clickup' => ['icon' => '✓', 'name' => 'ClickUp', 'connectors' => [], 'providers' => []],
            'erp' => ['icon' => '🏢', 'name' => 'ERP', 'connectors' => [], 'providers' => []],
            'other-tool' => ['icon' => '✦', 'name' => 'Otro', 'connectors' => [], 'providers' => []],
        ];
    }

    /**
     * @param  array<int, string>  $toolIds
     * @return array<int, array<string, mixed>>
     */
    public function forTools(array $toolIds): array
    {
        $tools = $this->tools();

        return array_values(array_filter(array_map(
            static function (string $id) use ($tools): ?array {
                if (! isset($tools[$id])) {
                    return null;
                }

                return [
                    'id' => $id,
                    'name' => $tools[$id]['name'],
                    'icon' => $tools[$id]['icon'],
                    'connectors' => $tools[$id]['connectors'] ?? [],
                    'providers' => $tools[$id]['providers'] ?? [],
                ];
            },
            array_values(array_unique($toolIds)),
        )));
    }

    /**
     * Resolve tool ids to connector and provider names.
     *
     * @param  array<int, string>  $ids
     * @return array<string, array<int, string>>
     */
    public function sourcesForTools(array $ids): array
    {
        $tools = $this->tools();
        $connectors = [];
        $providers = [];

        foreach ($ids as $id) {
            $connectors = array_merge($connectors, $tools[$id]['connectors'] ?? []);
            $providers = array_merge($providers, $tools[$id]['providers'] ?? []);
        }

        return [
            'connectors' => array_values(array_unique($connectors)),
            'providers' => array_values(array_unique($providers)),
        ];
    }
}
