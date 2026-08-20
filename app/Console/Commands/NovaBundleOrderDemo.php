<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Nova\NovaBundleOrderService;
use Illuminate\Console\Command;

final class NovaBundleOrderDemo extends Command
{
    protected $signature = 'nova:bundle-order-demo
        {--cancel : Cancel the created orders after creation}
        {--first_name=Prueba : Customer first name}
        {--last_name=Nova : Customer last name}
        {--email=poc@novagestion.eu : Customer email}
        {--phone=600000000 : Customer phone}
        {--agreements= : Comma-separated Lanzaloe checkout agreement IDs}';

    protected $description = 'Create a demo cross-platform bundle order (La Geria + Lanzaloe)';

    public function handle(NovaBundleOrderService $service): int
    {
        $this->info('Creating cross-platform bundle order...');

        $agreements = $this->option('agreements');
        $agreementIds = $agreements !== null && $agreements !== ''
            ? array_map('intval', explode(',', $agreements))
            : [];

        $result = $service->createBundle([
            'first_name' => $this->option('first_name'),
            'last_name' => $this->option('last_name'),
            'email' => $this->option('email'),
            'phone' => $this->option('phone'),
            'address' => 'Calle Prueba POC',
            'city' => 'Arrecife',
            'postcode' => '35500',
            'country' => 'ES',
            'region_id' => 157,
            'region_code' => 'Las Palmas',
            'region' => 'Las Palmas',
            'street' => ['Calle Prueba POC 1'],
            'company' => 'Novagestión Consultores, S.L.',
            'la_geria_product_id' => 240336,
            'la_geria_quantity' => 2,
            'lanzaloe_sku' => 'jugo_puro_250',
            'lanzaloe_quantity' => 1,
            'lanzaloe_shipping_method' => 'amstrates7',
            'lanzaloe_shipping_carrier' => 'amstrates',
            'lanzaloe_payment_method' => 'banktransfer',
            'lanzaloe_agreement_ids' => $agreementIds,
        ]);

        $this->newLine();
        $this->info('Bundle reference: '.($result['bundle_reference'] ?? 'N/A'));
        $this->info('Overall success: '.($result['success'] ? 'YES' : 'NO'));

        $this->newLine();
        $this->info('La Geria:');
        $this->line(json_encode($result['la_geria'] ?? [], JSON_PRETTY_PRINT));

        $this->newLine();
        $this->info('Lanzaloe:');
        $this->line(json_encode($result['lanzaloe'] ?? [], JSON_PRETTY_PRINT));

        if ($this->option('cancel')) {
            $this->newLine();
            $this->info('Cancelling orders...');

            $laGeriaId = $result['la_geria']['order_id'] ?? null;
            $lanzaloeId = $result['lanzaloe']['order_id'] ?? null;

            if (is_numeric($laGeriaId)) {
                $cancel = $service->cancelLaGeriaOrder((int) $laGeriaId);
                $this->info('La Geria cancel: '.json_encode($cancel));
            }

            if (is_numeric($lanzaloeId)) {
                $cancel = $service->cancelLanzaloeOrder((int) $lanzaloeId);
                $this->info('Lanzaloe cancel: '.json_encode($cancel));
            }
        }

        return self::SUCCESS;
    }
}
