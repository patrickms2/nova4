<?php

namespace App\Console\Commands;

use App\Models\Taxi\Position;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneTrackingPositionsCommand extends Command
{
    protected $signature = 'tracking:prune-positions {--days=7 : Retention window in days}';

    protected $description = 'Delete local tracking replay points older than the retention window';

    public function handle(): int
    {
        if (! Schema::hasTable('positions')) {
            $this->warn('La tabla positions no existe.');

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $deleted = Position::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where('fix_time', '<', $cutoff)
                    ->orWhere(function ($subQuery) use ($cutoff): void {
                        $subQuery->whereNull('fix_time')
                            ->where('device_time', '<', $cutoff);
                    })
                    ->orWhere(function ($subQuery) use ($cutoff): void {
                        $subQuery->whereNull('fix_time')
                            ->whereNull('device_time')
                            ->where('server_time', '<', $cutoff);
                    })
                    ->orWhere(function ($subQuery) use ($cutoff): void {
                        $subQuery->whereNull('fix_time')
                            ->whereNull('device_time')
                            ->whereNull('server_time')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->delete();

        $remaining = Position::query()->count();

        $this->info(sprintf(
            'Tracking positions pruned. Deleted: %d | Remaining: %d | Retention: %d days',
            $deleted,
            $remaining,
            $days,
        ));

        return self::SUCCESS;
    }
}
