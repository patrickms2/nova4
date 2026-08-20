<?php
declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\File;

final class NovaCapabilityRegistry
{
    public const DEFAULT_DEFINITION = [
        'workspace' => 'NOVA Community',
        'panel' => 'community',
        'groups' => [
            ['id'=>'property','label'=>'Propiedad','capabilities'=>[
                ['id'=>'properties','label'=>'Propiedades','tools'=>['view','edit','documents'],'roles'=>['owner','manager']],
                ['id'=>'documents','label'=>'Documentos','tools'=>['view','upload','download'],'roles'=>['owner','employee','manager']],
                ['id'=>'fees','label'=>'Cuotas','tools'=>['view','download'],'roles'=>['owner','manager']],
            ]],
            ['id'=>'community','label'=>'Comunidad','capabilities'=>[
                ['id'=>'communities','label'=>'Comunidades','tools'=>['view'],'roles'=>['employee','manager']],
                ['id'=>'notices','label'=>'Avisos','tools'=>['view'],'roles'=>['owner','employee','manager']],
                ['id'=>'incidents','label'=>'Incidencias','tools'=>['view','create','photo','priority','resolve'],'roles'=>['owner','employee','manager']],
                ['id'=>'tickets','label'=>'Tickets','tools'=>['view','create','comment'],'roles'=>['owner','employee','manager']],
                ['id'=>'appointments','label'=>'Citas','tools'=>['view','request','confirm'],'roles'=>['owner','employee','manager']],
            ]],
            ['id'=>'maintenance','label'=>'Mantenimiento','capabilities'=>[
                ['id'=>'plans','label'=>'Planes','tools'=>['view','generate-orders'],'roles'=>['employee','manager']],
                ['id'=>'work-orders','label'=>'Órdenes','tools'=>['view','start','complete','assign'],'roles'=>['employee','manager']],
                ['id'=>'tasks','label'=>'Tareas','tools'=>['view','check'],'roles'=>['employee','manager']],
                ['id'=>'shifts','label'=>'Turnos','tools'=>['view','start','finish'],'roles'=>['employee','manager']],
                ['id'=>'attendance','label'=>'Asistencia','tools'=>['view','register','voice-summary'],'roles'=>['employee','manager']],
                ['id'=>'expenses','label'=>'Gastos','tools'=>['view','create','ocr'],'roles'=>['employee','manager']],
            ]],
        ],
        'representations' => [
            'owner'=>['livewire'=>true,'filament'=>false],
            'employee'=>['livewire'=>true,'filament'=>false],
            'manager'=>['livewire'=>false,'filament'=>true],
        ],
    ];

    public function definition(): array
    {
        if (! File::exists($this->path())) {
            return self::DEFAULT_DEFINITION;
        }

        $decoded = json_decode((string) File::get($this->path()), true);

        return is_array($decoded) ? $decoded : self::DEFAULT_DEFINITION;
    }

    public function save(array $definition): void
    {
        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function reset(): void
    {
        $this->save(self::DEFAULT_DEFINITION);
    }

    public function representationEnabled(string $role, string $target): bool
    {
        return (bool) ($this->definition()['representations'][$role][$target] ?? false);
    }

    public function capabilityEnabled(string $role, string $capabilityId, string $target = 'livewire'): bool
    {
        if (! $this->representationEnabled($role, $target)) {
            return false;
        }

        foreach ($this->definition()['groups'] ?? [] as $group) {
            foreach ($group['capabilities'] ?? [] as $capability) {
                if (($capability['id'] ?? null) === $capabilityId) {
                    return in_array($role, $capability['roles'] ?? [], true);
                }
            }
        }

        return false;
    }

    public function toolEnabled(string $role, string $capabilityId, string $tool, string $target = 'livewire'): bool
    {
        if (! $this->capabilityEnabled($role, $capabilityId, $target)) {
            return false;
        }

        foreach ($this->definition()['groups'] ?? [] as $group) {
            foreach ($group['capabilities'] ?? [] as $capability) {
                if (($capability['id'] ?? null) !== $capabilityId) {
                    continue;
                }

                if (! in_array($tool, $capability['tools'] ?? [], true)) {
                    return false;
                }

                return ! in_array($role.'@'.$target.'@'.$tool, $capability['disabled_tools'] ?? [], true);
            }
        }

        return false;
    }

    private function path(): string
    {
        return storage_path('app/nova/capability-composer.json');
    }
}
