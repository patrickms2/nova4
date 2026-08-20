<?php

declare(strict_types=1);

namespace App\Support\Nova;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class FilamentStructureScanner
{
    /**
     * @return array<string,mixed>
     */
    public function scan(string $resourceFile): array
    {
        $source = File::get($resourceFile);

        $namespace = $this->match('/namespace\s+([^;]+);/', $source);
        $class = $this->match('/class\s+([A-Za-z0-9_]+)\s+extends\s+/', $source);

        $resource = [
            'file' => $resourceFile,
            'namespace' => $namespace,
            'class' => $class,
            'fqcn' => $namespace && $class ? $namespace.'\\'.$class : null,
            'model' => $this->extractClassProperty($source, 'model'),
            'navigation' => [
                'label' => $this->extractScalarProperty($source, 'navigationLabel'),
                'group' => $this->extractScalarProperty($source, 'navigationGroup'),
                'icon' => $this->extractScalarProperty($source, 'navigationIcon'),
                'sort' => $this->extractIntProperty($source, 'navigationSort'),
                'subnavigation_position' => $this->extractEnumProperty($source, 'subNavigationPosition'),
            ],
            'form' => $this->extractConfigureClass($source, 'form'),
            'infolist' => $this->extractConfigureClass($source, 'infolist'),
            'table' => $this->extractConfigureClass($source, 'table'),
            'relations' => $this->extractReturnedClasses($source, 'getRelations'),
            'widgets' => $this->extractReturnedClasses($source, 'getWidgets'),
            'pages' => $this->extractPages($source),
            'record_subnavigation' => $this->extractGenerateNavigationItems($source),
        ];

        $resource['page_details'] = [];
        foreach ($resource['pages'] as $key => $page) {
            $resolved = $this->resolveClassFile($resourceFile, $source, $page['class']);
            $resource['page_details'][$key] = $resolved
                ? $this->scanPage($resolved)
                : ['class' => $page['class'], 'missing' => true];
        }

        $resource['table_details'] = $this->scanReferencedClass($resourceFile, $source, $resource['table'], 'Tables');
        $resource['form_details'] = $this->scanReferencedClass($resourceFile, $source, $resource['form'], 'Schemas');
        $resource['infolist_details'] = $this->scanReferencedClass($resourceFile, $source, $resource['infolist'], 'Schemas');

        $resource['relation_details'] = [];
        foreach ($resource['relations'] as $relationClass) {
            $resolved = $this->resolveClassFile($resourceFile, $source, $relationClass);
            $resource['relation_details'][$relationClass] = $resolved
                ? $this->scanRelationManager($resolved)
                : ['class' => $relationClass, 'missing' => true];
        }

        $resource['widget_details'] = [];
        foreach ($resource['widgets'] as $widgetClass) {
            $resolved = $this->resolveClassFile($resourceFile, $source, $widgetClass);
            $resource['widget_details'][$widgetClass] = $resolved
                ? $this->scanWidget($resolved)
                : ['class' => $widgetClass, 'missing' => true];
        }

        return $resource;
    }

    /**
     * @return array<string,mixed>
     */
    public function scanPage(string $file): array
    {
        $source = File::get($file);

        return [
            'file' => $file,
            'class' => $this->match('/class\s+([A-Za-z0-9_]+)\s+extends\s+([A-Za-z0-9_\\\\]+)/', $source),
            'extends' => $this->match('/class\s+[A-Za-z0-9_]+\s+extends\s+([A-Za-z0-9_\\\\]+)/', $source),
            'tabs' => $this->extractTabs($source),
            'header_actions' => $this->extractActions($source, 'getHeaderActions'),
            'header_widgets' => $this->extractReturnedClasses($source, 'getHeaderWidgets'),
            'footer_widgets' => $this->extractReturnedClasses($source, 'getFooterWidgets'),
            'view' => $this->extractStaticView($source),
            'view_type' => $this->guessViewType($file, $source),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function scanRelationManager(string $file): array
    {
        $source = File::get($file);

        return [
            'file' => $file,
            'class' => $this->match('/class\s+([A-Za-z0-9_]+)\s+extends\s+/', $source),
            'relationship' => $this->extractScalarProperty($source, 'relationship'),
            'title' => $this->extractScalarProperty($source, 'title'),
            'table' => [
                'columns' => $this->extractMakeCalls($source, ['TextColumn', 'IconColumn', 'BadgeColumn', 'ImageColumn']),
                'filters' => $this->extractFilterCalls($source),
                'actions' => $this->extractActionCalls($source),
            ],
            'form' => [
                'fields' => $this->extractMakeCalls($source, [
                    'TextInput', 'Select', 'Toggle', 'DatePicker', 'DateTimePicker',
                    'Textarea', 'FileUpload', 'RichEditor', 'Checkbox', 'Radio',
                ]),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function scanWidget(string $file): array
    {
        $source = File::get($file);

        return [
            'file' => $file,
            'class' => $this->match('/class\s+([A-Za-z0-9_]+)\s+extends\s+([A-Za-z0-9_\\\\]+)/', $source),
            'extends' => $this->match('/class\s+[A-Za-z0-9_]+\s+extends\s+([A-Za-z0-9_\\\\]+)/', $source),
            'stats' => $this->extractStatCalls($source),
            'view_type' => $this->guessViewType($file, $source),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function scanReferencedClass(string $resourceFile, string $resourceSource, ?string $class, string $folder): ?array
    {
        if (! $class) {
            return null;
        }

        $resolved = $this->resolveClassFile($resourceFile, $resourceSource, $class);

        if (! $resolved) {
            return ['class' => $class, 'missing' => true];
        }

        $source = File::get($resolved);

        return [
            'file' => $resolved,
            'class' => $class,
            'columns' => $this->extractMakeCalls($source, ['TextColumn', 'IconColumn', 'BadgeColumn', 'ImageColumn']),
            'filters' => $this->extractFilterCalls($source),
            'actions' => $this->extractActionCalls($source),
            'fields' => $this->extractMakeCalls($source, [
                'TextInput', 'Select', 'Toggle', 'DatePicker', 'DateTimePicker',
                'Textarea', 'FileUpload', 'RichEditor', 'Checkbox', 'Radio',
            ]),
            'sections' => $this->extractMakeCalls($source, ['Section', 'Fieldset', 'Grid']),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private function extractPages(string $source): array
    {
        $body = $this->methodBody($source, 'getPages');

        if (! $body) {
            return [];
        }

        preg_match_all(
            "/['\"]([^'\"]+)['\"]\s*=>\s*([A-Za-z0-9_\\\\]+)::route\(\s*['\"]([^'\"]+)['\"]\s*\)/",
            $body,
            $matches,
            PREG_SET_ORDER
        );

        $pages = [];
        foreach ($matches as $match) {
            $pages[$match[1]] = [
                'class' => $match[2],
                'route' => $match[3],
            ];
        }

        return $pages;
    }

    /**
     * @return array<int,string>
     */
    private function extractGenerateNavigationItems(string $source): array
    {
        $body = $this->methodBody($source, 'getRecordSubNavigation');

        if (! $body) {
            return [];
        }

        if (! preg_match('/generateNavigationItems\s*\(\s*\[(.*?)\]\s*\)/s', $body, $match)) {
            return [];
        }

        preg_match_all('/([A-Za-z0-9_\\\\]+)::class/', $match[1], $classes);

        return array_values(array_unique($classes[1] ?? []));
    }

    /**
     * @return array<int,string>
     */
    private function extractReturnedClasses(string $source, string $method): array
    {
        $body = $this->methodBody($source, $method);

        if (! $body) {
            return [];
        }

        if (! preg_match('/return\s*\[(.*?)\]\s*;/s', $body, $match)) {
            return [];
        }

        preg_match_all('/([A-Za-z0-9_\\\\]+)::class/', $match[1], $classes);

        return array_values(array_unique($classes[1] ?? []));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractTabs(string $source): array
    {
        $body = $this->methodBody($source, 'getTabs');

        if (! $body) {
            return [];
        }

        preg_match_all(
            "/['\"]([^'\"]+)['\"]\s*=>\s*Tab::make\s*\(\s*\)(.*?)(?=,\s*['\"][^'\"]+['\"]\s*=>|,\s*\];|\n\s*\];)/s",
            $body,
            $matches,
            PREG_SET_ORDER
        );

        $tabs = [];
        foreach ($matches as $match) {
            $chain = $match[2];
            $tabs[] = [
                'key' => $match[1],
                'label' => $this->chainString($chain, 'label') ?: Str::headline($match[1]),
                'icon' => $this->chainString($chain, 'icon'),
                'badge' => str_contains($chain, '->badge('),
                'query' => str_contains($chain, 'modifyQueryUsing'),
            ];
        }

        return $tabs;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractActions(string $source, string $method): array
    {
        $body = $this->methodBody($source, $method);

        return $body ? $this->extractActionCalls($body) : [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractActionCalls(string $source): array
    {
        preg_match_all(
            '/([A-Za-z0-9_\\\\]*(?:Action|ActionGroup))::make\((.*?)\)(.*?)(?=,\s*[A-Za-z0-9_\\\\]*(?:Action|ActionGroup)::make|\n\s*\]|\z)/s',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        $items = [];
        foreach ($matches as $match) {
            $arg = trim($match[2]);
            $chain = $match[3];

            $items[] = [
                'type' => class_basename($match[1]),
                'key' => trim($arg, "'\" ") ?: Str::kebab(class_basename($match[1])),
                'label' => $this->chainString($chain, 'label'),
                'icon' => $this->chainString($chain, 'icon'),
                'color' => $this->chainString($chain, 'color'),
                'url' => str_contains($chain, '->url('),
                'modal' => str_contains($chain, '->modal'),
                'slide_over' => str_contains($chain, '->slideOver('),
            ];
        }

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractMakeCalls(string $source, array $types): array
    {
        $pattern = '/('.implode('|', array_map('preg_quote', $types)).')::make\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)(?=,\s*(?:'.implode('|', array_map('preg_quote', $types)).')::make|\n\s*\]|\z)/s';
        preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

        $items = [];
        foreach ($matches as $match) {
            $items[] = [
                'type' => $match[1],
                'field' => $match[2],
                'label' => $this->chainString($match[3], 'label'),
                'sortable' => str_contains($match[3], '->sortable(') || str_contains($match[3], '->sortable()'),
                'searchable' => str_contains($match[3], '->searchable(') || str_contains($match[3], '->searchable()'),
                'toggleable' => str_contains($match[3], '->toggleable(') || str_contains($match[3], '->toggleable()'),
            ];
        }

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractFilterCalls(string $source): array
    {
        preg_match_all(
            '/([A-Za-z0-9_\\\\]*(?:Filter))::make\(\s*[\'"]([^\'"]+)[\'"]\s*\)(.*?)(?=,\s*[A-Za-z0-9_\\\\]*(?:Filter)::make|\n\s*\]|\z)/s',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(fn ($match): array => [
            'type' => class_basename($match[1]),
            'key' => $match[2],
            'label' => $this->chainString($match[3], 'label'),
        ], $matches);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function extractStatCalls(string $source): array
    {
        preg_match_all(
            '/Stat::make\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)(.*?)(?=,\s*Stat::make|\n\s*\]|\z)/s',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(fn ($match): array => [
            'label' => $match[1],
            'value_expression' => trim($match[2]),
            'description' => $this->chainString($match[3], 'description'),
            'icon' => $this->chainString($match[3], 'descriptionIcon') ?: $this->chainString($match[3], 'icon'),
            'color' => $this->chainString($match[3], 'color'),
        ], $matches);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function extractConfigureClass(string $source, string $method): ?string
    {
        $body = $this->methodBody($source, $method);

        if (! $body) {
            return null;
        }

        return $this->match('/return\s+([A-Za-z0-9_\\\\]+)::configure\s*\(/', $body);
    }

    private function extractStaticView(string $source): ?string
    {
        return $this->match('/protected\s+string\s+\$view\s*=\s*[\'"]([^\'"]+)[\'"]/', $source);
    }

    private function guessViewType(string $file, string $source): string
    {
        $haystack = strtolower($file.' '.$source);

        return match (true) {
            str_contains($haystack, 'kanban') => 'kanban',
            str_contains($haystack, 'calendar') => 'calendar',
            str_contains($haystack, 'cuadrante'),
            str_contains($haystack, 'roster'),
            str_contains($haystack, 'schedule') => 'roster',
            str_contains($haystack, 'map') => 'map',
            str_contains($haystack, 'timeline') => 'timeline',
            default => 'page',
        };
    }

    private function resolveClassFile(string $resourceFile, string $resourceSource, string $class): ?string
    {
        $fqcn = $this->resolveFqcn($resourceSource, $class);

        if (! $fqcn || ! str_starts_with($fqcn, 'App\\')) {
            return null;
        }

        $relative = str_replace('\\', DIRECTORY_SEPARATOR, Str::after($fqcn, 'App\\')).'.php';
        $path = app_path($relative);

        if (File::exists($path)) {
            return $path;
        }

        // Useful for imported examples not physically under app/.
        $base = dirname($resourceFile);
        $short = class_basename($class);
        foreach (File::allFiles(dirname($base)) as $file) {
            if ($file->getFilename() === $short.'.php') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function resolveFqcn(string $source, string $class): ?string
    {
        if (str_contains($class, '\\')) {
            return ltrim($class, '\\');
        }

        if (preg_match('/use\s+([^;]+\\\\'.preg_quote($class, '/').');/', $source, $match)) {
            return trim($match[1]);
        }

        return null;
    }

    private function extractClassProperty(string $source, string $property): ?string
    {
        if (! preg_match('/\$'.preg_quote($property, '/').'\s*=\s*([A-Za-z0-9_\\\\]+)::class/', $source, $match)) {
            return null;
        }

        return $this->resolveFqcn($source, $match[1]) ?: $match[1];
    }

    private function extractScalarProperty(string $source, string $property): ?string
    {
        if (preg_match('/\$'.preg_quote($property, '/').'\s*=\s*[\'"]([^\'"]+)[\'"]/', $source, $match)) {
            return $match[1];
        }

        if (preg_match('/\$'.preg_quote($property, '/').'\s*=\s*([A-Za-z0-9_\\\\]+)::([A-Za-z0-9_]+)/', $source, $match)) {
            return $match[1].'::'.$match[2];
        }

        return null;
    }

    private function extractEnumProperty(string $source, string $property): ?string
    {
        return $this->extractScalarProperty($source, $property);
    }

    private function extractIntProperty(string $source, string $property): ?int
    {
        return preg_match('/\$'.preg_quote($property, '/').'\s*=\s*(\d+)/', $source, $match)
            ? (int) $match[1]
            : null;
    }

    private function chainString(string $chain, string $method): ?string
    {
        return preg_match('/->'.preg_quote($method, '/').'\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $chain, $match)
            ? $match[1]
            : null;
    }

    private function match(string $pattern, string $source): ?string
    {
        return preg_match($pattern, $source, $match) ? trim($match[1]) : null;
    }

    private function methodBody(string $source, string $method): ?string
    {
        $offset = strpos($source, 'function '.$method.'(');

        if ($offset === false) {
            return null;
        }

        $brace = strpos($source, '{', $offset);

        if ($brace === false) {
            return null;
        }

        $depth = 0;
        $length = strlen($source);

        for ($i = $brace; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    return substr($source, $brace + 1, $i - $brace - 1);
                }
            }
        }

        return null;
    }
}
