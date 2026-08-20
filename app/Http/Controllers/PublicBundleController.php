<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NovaBundleProduct;
use App\Services\Nova\NovaBundleOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PublicBundleController extends Controller
{
    public function index(Request $request): View
    {
        $ref = $request->query('ref');
        $bundleProduct = null;

        if ($ref) {
            $bundleProduct = NovaBundleProduct::where('reference', $ref)
                ->orWhere('id', $ref)
                ->where('status', true)
                ->first();
        }

        $bundleProducts = NovaBundleProduct::where('status', true)
            ->orderBy('name')
            ->get();

        return view('public.bundle', compact('bundleProduct', 'bundleProducts'));
    }

    public function store(Request $request, NovaBundleOrderService $bundleService): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postcode' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:2'],
            'region_id' => ['nullable', 'integer'],
            'region_code' => ['nullable', 'string', 'max:50'],
            'region' => ['nullable', 'string', 'max:120'],
            'street' => ['nullable', 'array'],
            'company' => ['nullable', 'string', 'max:120'],
            'la_geria_product_id' => ['nullable', 'integer'],
            'la_geria_quantity' => ['nullable', 'integer', 'min:1'],
            'lanzaloe_sku' => ['nullable', 'string', 'max:120'],
            'lanzaloe_quantity' => ['nullable', 'integer', 'min:1'],
            'lanzaloe_shipping_method' => ['nullable', 'string', 'max:120'],
            'lanzaloe_shipping_carrier' => ['nullable', 'string', 'max:120'],
            'lanzaloe_payment_method' => ['nullable', 'string', 'max:120'],
            'lanzaloe_agreement_ids' => ['nullable', 'array'],
            'cancel_after' => ['nullable', 'boolean'],
        ]);

        $result = $bundleService->createBundle($validated);

        return response()->json([
            'success' => $result['success'],
            'bundle_reference' => $result['bundle_reference'],
            'bundle_id' => $result['record_id'],
            'payment_url' => $result['success'] ? route('bundle.redsys.start', ['bundle' => $result['record_id']]) : null,
            'la_geria' => $result['la_geria'],
            'lanzaloe' => $result['lanzaloe'],
        ]);
    }
}
