<?php

namespace App\Http\Controllers;

use App\Models\NovaTaxiRouteDraft;
use Illuminate\Contracts\View\View;

class PublicTaxiRouteCheckoutController extends Controller
{
    public function show(string $token): View
    {
        $draft = NovaTaxiRouteDraft::query()
            ->where('token', $token)
            ->firstOrFail();

        return view('taxi-routes.checkout', [
            'draft' => $draft,
        ]);
    }
}
