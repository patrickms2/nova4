<?php

namespace App\Console\Commands\Nova;

use App\Models\NovaListingCategory;
use App\Models\Prompt;
use App\Models\Server;
use App\Models\Tool;
use Illuminate\Console\Command;

class ImportListingFromPrompts extends Command
{
    protected $signature = 'nova:import-listing-from-prompts
                            {--dry-run : Show what would be imported without saving}
                            {--force  : Overwrite existing categories}';

    protected $description = 'Import/sync NovaListingCategory records from prompt listing metadata (listing_intro, listing_tool, listing_cta).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force  = $this->option('force');

        $prompts = Prompt::whereNotNull('metadata')->get()->filter(
            fn (Prompt $p) => ! empty($p->metadata['listing_intro'])
        );

        if ($prompts->isEmpty()) {
            $this->info('No prompts with listing metadata found.');

            return self::SUCCESS;
        }

        $this->table(
            ['Prompt', 'Server', 'Business', 'Tool', 'Status'],
            $prompts->map(function (Prompt $prompt) use ($dryRun, $force) {
                $meta   = $prompt->metadata;
                $server = Server::find($prompt->server_id);

                if (! $server?->nova_business_id) {
                    return [$prompt->name, $server?->name ?? '—', '—', '—', '⚠ No business'];
                }

                $busId     = $server->nova_business_id;
                $toolSlug  = $meta['listing_tool'] ?? null;
                $tool      = $toolSlug
                    ? Tool::where('name', $toolSlug)->where('server_id', $server->id)->first()
                    : null;

                $existing = NovaListingCategory::where('nova_business_id', $busId)
                    ->where('server_id', $server->id)
                    ->first();

                if ($existing && ! $force) {
                    return [$prompt->name, $server->name, $busId, $toolSlug ?? '—', '✓ Already exists (skip)'];
                }

                $slug = $existing?->slug ?? ($toolSlug ? str($toolSlug)->slug()->toString() : 'listing');

                $data = [
                    'nova_business_id' => $busId,
                    'server_id'        => $server->id,
                    'tool_id'          => $tool?->id,
                    'slug'             => $slug,
                    'keywords'         => $existing?->keywords ?? [],
                    'intro_text'       => $meta['listing_intro'],
                    'cta_text'         => $meta['listing_cta'] ?? null,
                    'is_active'        => true,
                    'sort_order'       => NovaListingCategory::where('nova_business_id', $busId)->count() + 1,
                ];

                if (! $dryRun) {
                    $existing
                        ? $existing->update($data)
                        : NovaListingCategory::create($data);
                }

                $action = $existing ? ($dryRun ? '~ Would update' : '↑ Updated') : ($dryRun ? '+ Would create' : '+ Created');

                return [$prompt->name, $server->name, $busId, $toolSlug ?? '—', $action];
            })->toArray()
        );

        $this->info($dryRun ? 'Dry run complete — nothing was saved.' : 'Import complete.');

        return self::SUCCESS;
    }
}
