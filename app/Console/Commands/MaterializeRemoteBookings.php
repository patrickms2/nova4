<?php

namespace App\Console\Commands;

use App\Models\ExternalBooking;
use App\Models\PublicBookingRequest;
use App\Services\ExternalSync\RemoteBookingCreator;
use Illuminate\Console\Command;

class MaterializeRemoteBookings extends Command
{
    protected $signature = 'external:materialize-remote-bookings {--limit=200}';

    protected $description = 'Materialize remote-created bookings into local models so they appear in Filament without waiting for a sync.';

    public function handle(RemoteBookingCreator $creator): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $requests = PublicBookingRequest::query()
            ->where('remote_booking_status', 'created')
            ->where('remote_source_platform', 'latepoint')
            ->whereNotNull('remote_external_id')
            ->latest('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        $materialized = 0;
        $skipped = 0;

        foreach ($requests as $request) {
            $processed++;

            $existing = ExternalBooking::query()
                ->where('source_platform', 'latepoint')
                ->where('external_id', (string) $request->remote_external_id)
                ->first();

            if ($existing) {
                // Backfill resource_type / target_model for pre-migration rows.
                if (blank($existing->resource_type) || blank($existing->target_model)) {
                    if ($creator->materializeLatepointRequest($request)) {
                        $materialized++;
                        continue;
                    }
                }

                $skipped++;
                continue;
            }

            if ($creator->materializeLatepointRequest($request)) {
                $materialized++;
            } else {
                $skipped++;
            }
        }

        $this->info("Processed: {$processed}. Materialized: {$materialized}. Skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
