<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CommunityServiceClassificationTest extends TestCase
{
    public function test_rental_contacts_expose_work_categories(): void
    {
        $root = dirname(__DIR__, 2);
        $model = file_get_contents($root.'/app/Models/RentalContact.php');
        $resource = file_get_contents($root.'/app/Filament/App/Rentals/Resources/RentalContactResource.php');

        $this->assertStringContainsString('function workCategories(): BelongsToMany', $model);
        $this->assertStringContainsString("Select::make('workCategories')", $resource);
        $this->assertStringContainsString("relationship('workCategories', 'name')", $resource);
    }

    public function test_owner_and_employee_incidents_select_type_and_optional_service(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/app/Livewire/CommunityPortal.php');
        $view = file_get_contents($root.'/resources/views/livewire/community-portal.blade.php');

        $this->assertStringContainsString("'incidentWorkCategoryId' => ['required'", $component);
        $this->assertStringContainsString("'incidentWorkCatalogId' => ['nullable'", $component);
        $this->assertStringContainsString("'work_category_id' => \$validated['incidentWorkCategoryId']", $component);
        $this->assertStringContainsString("'work_catalog_id' => \$validated['incidentWorkCatalogId'] ?? null", $component);
        $this->assertSame(2, substr_count($view, 'wire:model.live="incidentWorkCategoryId"'));
        $this->assertSame(2, substr_count($view, 'wire:model="incidentWorkCatalogId"'));
    }

    public function test_service_must_belong_to_the_selected_type(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/app/Livewire/CommunityPortal.php');

        $this->assertStringContainsString("where('work_category_id', \$this->incidentWorkCategoryId)", $component);
    }
}
