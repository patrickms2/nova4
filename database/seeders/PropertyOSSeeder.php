<?php

namespace Database\Seeders;

use App\Models\RentalExpense;
use App\Models\RentalGuest;
use App\Models\RentalProperty;
use App\Models\RentalReservation;
use App\Services\Rental\RentalSettlementCalculator;
use Illuminate\Database\Seeder;

class PropertyOSSeeder extends Seeder
{
    public function run(): void
    {
        $property = RentalProperty::firstOrCreate(
            ['code' => 'casa-el-patio'],
            [
                'name' => 'Casa El Patio',
                'financial_settings' => [
                    'manager_commission_rate' => '30',
                    'manager_name' => 'Bayside Corp, S.L.',
                    'cleaning_per_stay' => '90',
                    'cleaning_provider' => 'Proveedor limpieza',
                    'laundry_per_guest' => '15',
                    'welcome_pack' => '25',
                    'damage_waiver' => '20',
                ],
                'is_active' => true,
            ]
        );

        $reservations = [
            [
                'first_name' => 'Titassr',
                'last_name' => 'Massinga',
                'email' => 'titassr.massinga@example.com',
                'check_in' => '2026-08-02',
                'check_out' => '2026-08-08',
                'adults' => 2,
                'children' => 0,
                'amount' => 1296.00,
                'channel' => 'agency',
                'reference_code' => 'MASS-20260802',
            ],
            [
                'first_name' => 'Felicie',
                'last_name' => 'Derville',
                'email' => 'felicie.derville@example.com',
                'check_in' => '2026-08-08',
                'check_out' => '2026-08-11',
                'adults' => 2,
                'children' => 0,
                'amount' => 699.30,
                'channel' => 'agency',
                'reference_code' => 'DERV-20260808',
            ],
            [
                'first_name' => 'Elisabet',
                'last_name' => 'Barranco',
                'email' => 'elisabet.barranco@example.com',
                'check_in' => '2026-08-13',
                'check_out' => '2026-08-19',
                'adults' => 2,
                'children' => 0,
                'amount' => 1348.88,
                'channel' => 'direct',
                'reference_code' => 'BARR-20260813',
            ],
            [
                'first_name' => 'Mohamed',
                'last_name' => 'Rauch',
                'email' => 'mohamed.rauch@example.com',
                'check_in' => '2026-08-20',
                'check_out' => '2026-09-05',
                'adults' => 2,
                'children' => 0,
                'amount' => 3717.00,
                'channel' => 'agency',
                'reference_code' => 'RAUC-20260820',
            ],
        ];

        foreach ($reservations as $data) {
            $guest = RentalGuest::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                ]
            );

            $reservation = RentalReservation::updateOrCreate(
                ['reference_code' => $data['reference_code']],
                [
                    'rental_property_id' => $property->id,
                    'guest_id' => $guest->id,
                    'channel' => $data['channel'],
                    'check_in' => $data['check_in'],
                    'check_out' => $data['check_out'],
                    'adults' => $data['adults'],
                    'children' => $data['children'],
                    'amount' => $data['amount'],
                    'channel_commission' => 0,
                    'cleaning_fee' => 0,
                    'payout' => 0,
                    'status' => 'confirmed',
                    'raw_payload' => $data,
                    'parsed_at' => now(),
                ]
            );

            RentalSettlementCalculator::for($reservation)->calculate();
        }

        $expenses = [
            ['category' => 'luz', 'description' => 'Luz'],
            ['category' => 'agua', 'description' => 'Agua'],
            ['category' => 'internet', 'description' => 'Internet'],
            ['category' => 'seguro', 'description' => 'Seguro'],
            ['category' => 'ibi', 'description' => 'IBI'],
            ['category' => 'basura', 'description' => 'Basura'],
            ['category' => 'jardin', 'description' => 'Jardinería'],
            ['category' => 'piscina', 'description' => 'Piscina'],
            ['category' => 'amazon', 'description' => 'Amazon'],
            ['category' => 'reformas', 'description' => 'Reparaciones'],
            ['category' => 'mantenimiento', 'description' => 'Mantenimiento'],
        ];

        foreach ($expenses as $expense) {
            RentalExpense::firstOrCreate(
                [
                    'rental_property_id' => $property->id,
                    'description' => $expense['description'],
                    'expense_date' => now()->startOfMonth()->toDateString(),
                ],
                [
                    'category' => $expense['category'],
                    'base_amount' => 0,
                    'tax_amount' => 0,
                    'total_amount' => 0,
                    'status' => 'pending',
                    'is_recurrent' => false,
                ]
            );
        }
    }
}
