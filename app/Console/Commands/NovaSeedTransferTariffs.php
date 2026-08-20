<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TransferTariff;
use Illuminate\Console\Command;

final class NovaSeedTransferTariffs extends Command
{
    protected $signature = 'nova:seed-transfer-tariffs';

    protected $description = 'Seed editable Taxilanz transfer tariffs';

    public function handle(): int
    {
        $count = 0;

        foreach ($this->fares() as $origin => $destinations) {
            foreach ($destinations as $destination => $price) {
                TransferTariff::query()->updateOrCreate(
                    ['origin_zone' => $origin, 'destination_zone' => $destination],
                    [
                        'price' => $price,
                        'currency' => 'EUR',
                        'holiday_surcharge_percent' => 15,
                        'igic_percent' => 7,
                        'igic_included' => false,
                        'is_active' => true,
                    ],
                );

                $count++;
            }
        }

        $this->info("Seeded {$count} transfer tariffs.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, float|int>>
     */
    private function fares(): array
    {
        return [
            'playa blanca' => ['aeropuerto' => 59, 'puerto del carmen' => 45, 'matagorda' => 50, 'arrecife' => 59, 'los marmoles' => 64, 'la marina' => 64, 'teguise' => 63, 'playa honda' => 52, 'jardin del cactus' => 72, 'costa teguise' => 69, 'la santa' => 58, 'jameos del agua' => 84, 'caleta de famara' => 71, 'mirador del rio' => 91, 'orzola' => 101, 'el golfo' => 26, 'la geria' => 33, 'timanfaya' => 37, 'yaiza' => 24, 'playa quemada' => 36, 'puerto calero' => 40, 'haria' => 84],
            'puerto del carmen' => ['aeropuerto' => 25, 'arrecife' => 25, 'los marmoles' => 30, 'la marina' => 27, 'playa honda' => 26, 'costa teguise' => 39, 'teguise' => 35, 'jardin del cactus' => 44, 'la santa' => 48, 'jameos del agua' => 64, 'caleta de famara' => 45, 'mirador del rio' => 71, 'orzola' => 74, 'el golfo' => 37, 'la geria' => 21, 'timanfaya' => 37, 'yaiza' => 22, 'playa quemada' => 21, 'puerto calero' => 20, 'playa blanca' => 52],
            'matagorda' => ['aeropuerto' => 20, 'arrecife' => 22, 'los marmoles' => 30, 'la marina' => 22, 'playa honda' => 16, 'costa teguise' => 34, 'teguise' => 34, 'jardin del cactus' => 37, 'la santa' => 47, 'jameos del agua' => 57, 'caleta de famara' => 47, 'mirador del rio' => 71, 'orzola' => 68, 'el golfo' => 44, 'la geria' => 28, 'timanfaya' => 45, 'yaiza' => 30, 'playa quemada' => 21, 'puerto calero' => 20, 'playa blanca' => 55],
            'costa teguise' => ['aeropuerto' => 31, 'puerto del carmen' => 36, 'arrecife' => 17, 'los marmoles' => 14, 'la marina' => 17, 'teguise' => 24, 'playa honda' => 24, 'jardin del cactus' => 22, 'jameos del agua' => 38, 'caleta de famara' => 40, 'la santa' => 48, 'mirador del rio' => 52, 'orzola' => 51, 'el golfo' => 59, 'la geria' => 41, 'timanfaya' => 61, 'yaiza' => 47, 'playa quemada' => 48, 'puerto calero' => 44, 'playa blanca' => 71],
            'haria' => ['aeropuerto' => 53, 'puerto del carmen' => 63, 'arrecife' => 46, 'teguise' => 25, 'orzola' => 18, 'jardin del cactus' => 20, 'la santa' => 57, 'jameos del agua' => 16, 'mirador del rio' => 14, 'playa blanca' => 84, 'el golfo' => 82, 'timanfaya' => 67, 'puerto calero' => 63],
            'tinajo' => ['aeropuerto' => 35, 'puerto del carmen' => 37, 'arrecife' => 29, 'tias' => 28, 'caleta de famara' => 35, 'teguise' => 27, 'costa teguise' => 40, 'playa honda' => 29, 'playa blanca' => 47, 'el golfo' => 44, 'timanfaya' => 18, 'yaiza' => 25, 'puerto calero' => 33],
            'la santa sport' => ['aeropuerto' => 47, 'jardin del cactus' => 48, 'costa teguise' => 51, 'jameos del agua' => 59, 'mirador del rio' => 69, 'playa blanca' => 56, 'playa quemada' => 52],
        ];
    }
}
