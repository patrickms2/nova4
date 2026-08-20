<?php

declare(strict_types=1);

namespace App\Http\Controllers\Nova;

use App\Domain\Nova\Studio\Workspace\Graph\CapabilityNodeOptions;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only resolver for VoodFlow's Capability "+" contextual menu.
 *
 * VoodFlow calls this to ask "what can be added to this Capability?"
 * instead of hardcoding the answer, per NOVA_GRAPH.md → "VOODFLOW":
 * VoodFlow is the interaction/rendering layer, never the source of truth.
 */
final class CapabilityGraphOptionsController extends Controller
{
    public function __invoke(Request $request, CapabilityNodeOptions $options): JsonResponse
    {
        $capabilityId = (string) $request->query('capability', '');

        if ($capabilityId === '') {
            return response()->json([
                'message' => 'The capability query parameter is required.',
            ], 422);
        }

        return response()->json($options->forCapability($capabilityId));
    }
}
