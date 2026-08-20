<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Support\CommunityCapabilityRuntime;
use App\Support\NovaCapabilityRegistry;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class NovaCapabilityRuntimeTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(storage_path('app/nova/capability-composer.json'));
        parent::tearDown();
    }

    public function test_owner_sections_follow_definition(): void
    {
        $registry = app(NovaCapabilityRegistry::class);
        $definition = $registry->definition();

        foreach ($definition['groups'] as $gi => $group) {
            foreach ($group['capabilities'] as $ci => $capability) {
                if ($capability['id'] === 'incidents') {
                    $definition['groups'][$gi]['capabilities'][$ci]['roles'] = ['employee','manager'];
                }
            }
        }

        $registry->save($definition);
        $runtime = app(CommunityCapabilityRuntime::class);

        $this->assertFalse($runtime->sectionEnabled('owner', 'incidents'));
        $this->assertTrue($runtime->sectionEnabled('owner', 'properties'));
        $this->assertTrue($runtime->sectionEnabled('owner', 'home'));
    }
}
