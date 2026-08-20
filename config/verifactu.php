<?php

return array_merge(
    require base_path('vendor/squareetlabs/laravel-verifactu/config/verifactu.php'),
    [
        'regime_type' => env('VERIFACTU_REGIME_TYPE', '08'),

        'igic_rates' => [0.00, 3.00, 7.00, 9.50, 15.00, 20.00],
    ]
);
