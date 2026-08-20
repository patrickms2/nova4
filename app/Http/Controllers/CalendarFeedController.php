<?php

namespace App\Http\Controllers;

use Crumbls\Timeline\Models\Occurrence;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarFeedController extends Controller
{
    public function __invoke(Request $request)
    {
        $start = Carbon::parse($request->get('start', Carbon::now()->startOfMonth()));
        $end   = Carbon::parse($request->get('end', Carbon::now()->endOfMonth()));

        $occurrences = Occurrence::between($start, $end)
            ->scheduled()
            ->with(['event', 'location', 'exceptions'])
            ->orderBy('starts_at')
            ->get();

        return response()->json(
            $occurrences->map(fn ($o) => $this->transform($o))
        );
    }

    private function transform(Occurrence $occurrence): array
    {
        return [
            'id'         => $occurrence->uuid,
            'title'      => $occurrence->event->name,
            'start'      => $occurrence->starts_at->toIso8601String(),
            'end'        => $occurrence->ends_at?->toIso8601String(),
            'status'     => $occurrence->status->value,
            'location'   => $occurrence->location ? [
                'name'    => $occurrence->location->name,
                'address' => $occurrence->location->address_1,
                'city'    => $occurrence->location->city,
                'lat'     => $occurrence->location->latitude,
                'lng'     => $occurrence->location->longitude,
            ] : null,
        ];
    }
}