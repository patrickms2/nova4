<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaRepresentationStatus;
use App\Enums\Nova\NovaRepresentationType;
use App\Enums\Nova\NovaResourceType;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaRepresentation;
use App\Models\Nova\NovaResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

final class FilamentResourceDiscovery
{
    /**
     * Discover Filament Resources from app/Filament recursively.
     *
     * @return Collection<int, NovaRepresentation>
     */
    public function discover(bool $persist = true): Collection
    {
        $files = collect(File::allFiles(app_path('Filament')))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), 'Resource.php'))
            ->values();

        $representations = collect();

        foreach ($files as $file) {
            $metadata = $this->inspectFile($file->getPathname());

            if ($metadata === null) {
                continue;
            }

            $representations->push(
                $persist ? $this->persist($metadata) : $metadata
            );
        }

        return $representations;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function inspectFile(string $path): ?array
    {
        $contents = File::get($path);

        if (! preg_match('/namespace\s+([^;]+);/', $contents, $namespaceMatch)) {
            return null;
        }

        if (! preg_match('/class\s+([A-Za-z0-9_]+)\s+extends\s+([A-Za-z0-9_\\\\]+)/', $contents, $classMatch)) {
            return null;
        }

        $namespace = trim($namespaceMatch[1]);
        $class = trim($classMatch[1]);
        $fqcn = $namespace.'\\'.$class;

        if (! str_ends_with($class, 'Resource')) {
            return null;
        }

        $modelClass = $this->extractStaticStringOrClass($contents, 'model');
        $navigationGroup = $this->extractStaticString($contents, 'navigationGroup');
        $navigationLabel = $this->extractStaticString($contents, 'navigationLabel');
        $navigationIcon = $this->extractStaticString($contents, 'navigationIcon');
        $navigationSort = $this->extractStaticInteger($contents, 'navigationSort');

        $panelGuess = $this->guessPanelKey($path);
        $resourceKey = $modelClass
            ? Str::of(class_basename($modelClass))->kebab()->toString()
            : Str::of($class)->beforeLast('Resource')->kebab()->toString();

        return [
            'type' => NovaRepresentationType::Filament,
            'status' => NovaRepresentationStatus::Detected,
            'key' => 'filament.'.Str::of($fqcn)->replace('\\', '.')->lower()->toString(),
            'name' => Str::of($class)->beforeLast('Resource')->headline()->toString(),
            'class_name' => $fqcn,
            'model_class' => $modelClass,
            'navigation_group' => $navigationGroup,
            'navigation_label' => $navigationLabel,
            'navigation_icon' => $navigationIcon,
            'navigation_sort' => $navigationSort,
            'panel_guess' => $panelGuess,
            'resource_key' => $resourceKey,
            'settings' => [
                'path' => Str::after($path, base_path().DIRECTORY_SEPARATOR),
                'discovered_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function persist(array $metadata): NovaRepresentation
    {
        $resource = $this->matchResource($metadata);
        $panel = $this->matchPanel($metadata);
        $capability = $this->matchCapability($metadata, $resource, $panel);

        $status = ($resource && $capability)
            ? NovaRepresentationStatus::Matched
            : NovaRepresentationStatus::Detected;

        return NovaRepresentation::query()->updateOrCreate(
            ['class_name' => $metadata['class_name']],
            [
                'workspace_id' => $panel?->workspace_id,
                'panel_id' => $panel?->id,
                'resource_id' => $resource?->id,
                'capability_id' => $capability?->id,
                'type' => NovaRepresentationType::Filament,
                'status' => $status,
                'key' => $metadata['key'],
                'name' => $metadata['name'],
                'model_class' => $metadata['model_class'],
                'navigation_group' => $metadata['navigation_group'],
                'navigation_label' => $metadata['navigation_label'],
                'navigation_icon' => $metadata['navigation_icon'],
                'navigation_sort' => $metadata['navigation_sort'],
                'settings' => $metadata['settings'],
            ]
        );
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function matchResource(array $metadata): ?NovaResource
    {
        if (! empty($metadata['model_class'])) {
            $exact = NovaResource::query()
                ->where('class_name', $metadata['model_class'])
                ->first();

            if ($exact) {
                return $exact;
            }
        }

        $key = (string) $metadata['resource_key'];

        return NovaResource::query()->firstOrCreate(
            ['key' => $key],
            [
                'name' => Str::of($key)->replace('-', ' ')->headline()->toString(),
                'type' => NovaResourceType::EloquentModel,
                'class_name' => $metadata['model_class'],
                'source' => 'filament-discovery',
                'settings' => [],
            ]
        );
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function matchPanel(array $metadata): ?NovaPanel
    {
        $guess = $metadata['panel_guess'];

        if ($guess) {
            $panel = NovaPanel::query()->where('key', $guess)->first();
            if ($panel) {
                return $panel;
            }
        }

        return NovaPanel::query()->where('key', 'community')->first()
            ?? NovaPanel::query()->orderBy('id')->first();
    }

    /**
     * @param array<string,mixed> $metadata
     */
    private function matchCapability(array $metadata, ?NovaResource $resource, ?NovaPanel $panel): ?NovaCapability
    {
        if ($resource) {
            $capability = NovaCapability::query()
                ->whereHas('resources', fn ($query) => $query->where('nova_resources.id', $resource->id))
                ->whereHas('bindings', fn ($query) => $query->when(
                    $panel,
                    fn ($bindingQuery) => $bindingQuery->where('panel_id', $panel->id)
                ))
                ->first();

            if ($capability) {
                return $capability;
            }
        }

        $needle = Str::of((string) $metadata['resource_key'])->singular()->toString();

        return NovaCapability::query()
            ->where('key', 'like', '%.'.$needle)
            ->orWhere('key', 'like', '%.'.$needle.'s')
            ->first();
    }

    private function guessPanelKey(string $path): ?string
    {
        $normalized = str_replace('\\', '/', $path);

        return match (true) {
            str_contains($normalized, '/Filament/Community/') => 'community',
            str_contains($normalized, '/Filament/App/') => 'app',
            str_contains($normalized, '/Filament/Portal/') => 'portal',
            str_contains($normalized, '/Rentals/'),
            str_contains($normalized, '/Rental/') => 'rent',
            str_contains($normalized, '/Domotics/'),
            str_contains($normalized, '/Access/') => 'access',
            default => null,
        };
    }

    private function extractStaticString(string $contents, string $property): ?string
    {
        $pattern = '/(?:protected|public|private)\s+static\s+(?:\?string|string)\s+\$'.$property.'\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/';

        return preg_match($pattern, $contents, $match) ? $match[1] : null;
    }

    private function extractStaticInteger(string $contents, string $property): ?int
    {
        $pattern = '/(?:protected|public|private)\s+static\s+(?:\?int|int)\s+\$'.$property.'\s*=\s*(\d+)\s*;/';

        return preg_match($pattern, $contents, $match) ? (int) $match[1] : null;
    }

    private function extractStaticStringOrClass(string $contents, string $property): ?string
    {
        $string = $this->extractStaticString($contents, $property);

        if ($string) {
            return $string;
        }

        $pattern = '/(?:protected|public|private)\s+static\s+(?:\?string|string)\s+\$'.$property.'\s*=\s*([A-Za-z0-9_\\\\]+)::class\s*;/';

        if (! preg_match($pattern, $contents, $match)) {
            return null;
        }

        $short = $match[1];

        if (str_contains($short, '\\')) {
            return ltrim($short, '\\');
        }

        if (preg_match('/use\s+([^;\\\\]+\\\\'.$short.');/', $contents, $useMatch)) {
            return trim($useMatch[1]);
        }

        return $short;
    }
}
