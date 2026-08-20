<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class NovaDesignSystemTest extends TestCase
{
    public function test_mission_result_card_renders_a_business_achievement(): void
    {
        $html = Blade::render('<x-nova.mission-result-card :result="$result" />', [
            'result' => [
                'goal' => 'Preparar facturas',
                'summary' => '12 facturas preparadas',
                'files' => [['name' => 'facturas.pdf']],
            ],
        ]);

        $this->assertStringContainsString('Preparar facturas', $html);
        $this->assertStringContainsString('12 facturas preparadas', $html);
        $this->assertStringContainsString('1 archivo disponible', $html);
    }

    public function test_card_renders_its_slot(): void
    {
        $html = Blade::render('<x-nova.card>Workspace</x-nova.card>');

        $this->assertStringContainsString('Workspace', $html);
        $this->assertStringContainsString('rounded-2xl', $html);
    }

    public function test_workspace_preview_renders_business_navigation(): void
    {
        $html = Blade::render('<x-nova.workspace-preview :workspace="$workspace" />', [
            'workspace' => [
                'business_name' => 'Tienda',
                'navigation' => [
                    ['id' => 'home', 'icon' => '⌂', 'name' => 'Inicio', 'improvements' => []],
                    ['id' => 'orders', 'icon' => '📦', 'name' => 'Pedidos', 'improvements' => ['shipping']],
                ],
            ],
        ]);

        $this->assertStringContainsString('NOVA Workspace', $html);
        $this->assertStringContainsString('Tienda', $html);
        $this->assertStringContainsString('Pedidos', $html);
        $this->assertStringContainsString('Así se organiza ahora tu Workspace.', $html);
        $this->assertStringContainsString('bg-orange-500/10', $html);
    }

    public function test_section_heading_renders_eyebrow_title_and_meta(): void
    {
        $html = Blade::render('<x-nova.section-heading eyebrow="Planner" title="Execution plan" meta="4 steps" />');

        $this->assertStringContainsString('Planner', $html);
        $this->assertStringContainsString('Execution plan', $html);
        $this->assertStringContainsString('4 steps', $html);
    }

    public function test_status_pill_maps_status_to_tone(): void
    {
        $this->assertStringContainsString('text-neutral-400', Blade::render('<x-nova.status-pill status="ready" />'));
        $this->assertStringContainsString('text-orange-400', Blade::render('<x-nova.status-pill status="running" />'));
        $this->assertStringContainsString('text-green-400', Blade::render('<x-nova.status-pill status="completed" />'));
        $this->assertStringContainsString('text-red-400', Blade::render('<x-nova.status-pill status="failed" />'));
        $this->assertStringContainsString('text-neutral-400', Blade::render('<x-nova.status-pill status="available" />'));
    }

    public function test_status_badge_delegates_to_status_pill(): void
    {
        $this->assertSame(
            trim(Blade::render('<x-nova.status-pill status="ready" />')),
            trim(Blade::render('<x-nova.status-badge status="ready" />')),
        );
    }

    public function test_skeleton_renders_the_requested_number_of_lines(): void
    {
        $html = Blade::render('<x-nova.skeleton :lines="4" />');

        $this->assertSame(4, substr_count($html, 'animate-pulse'));
    }

    public function test_command_center_renders_input_and_suggestions(): void
    {
        $html = Blade::render('<x-nova.command-center :suggestions="$suggestions" />', [
            'suggestions' => ['Create an invoice'],
        ]);

        $this->assertStringContainsString('wire:submit="submitPrompt"', $html);
        $this->assertStringContainsString('wire:model="prompt"', $html);
        $this->assertStringContainsString('Create an invoice', $html);
    }

    public function test_planner_timeline_renders_steps_and_approval(): void
    {
        $html = Blade::render('<x-nova.planner-timeline goal="Launch campaign" :steps="$steps" :progress="50" approve-action="approvePlan" />', [
            'steps' => [
                ['title' => 'Collect audience', 'status' => 'done', 'agent' => 'CRM'],
                ['title' => 'Send campaign', 'status' => 'pending', 'connector' => 'Meta'],
            ],
        ]);

        $this->assertStringContainsString('Launch campaign', $html);
        $this->assertStringContainsString('Collect audience', $html);
        $this->assertStringContainsString('Send campaign', $html);
        $this->assertStringContainsString('wire:click="approvePlan"', $html);
        $this->assertStringContainsString('width: 50%', $html);
    }

    public function test_planner_timeline_renders_empty_state_without_steps(): void
    {
        $html = Blade::render('<x-nova.planner-timeline />');

        $this->assertStringContainsString('Todavía no hay ningún plan', $html);
    }

    public function test_activity_feed_shows_newest_first_and_respects_limit(): void
    {
        $html = Blade::render('<x-nova.activity-feed :events="$events" :limit="2" />', [
            'events' => [
                ['title' => 'Oldest event'],
                ['title' => 'Middle event'],
                ['title' => 'Newest event'],
            ],
        ]);

        $this->assertStringNotContainsString('Oldest event', $html);
        $this->assertLessThan(
            strpos($html, 'Middle event'),
            strpos($html, 'Newest event'),
        );
    }

    public function test_activity_feed_renders_empty_state(): void
    {
        $this->assertStringContainsString('Todavía no hay actividad', Blade::render('<x-nova.activity-feed />'));
    }

    public function test_agent_card_renders_responsibility_and_connectors(): void
    {
        $html = Blade::render('<x-nova.agent-card name="Planner" responsibility="Turns intent into a plan" :connectors="$connectors" status="idle" />', [
            'connectors' => ['OpenAI'],
        ]);

        $this->assertStringContainsString('Planner', $html);
        $this->assertStringContainsString('Turns intent into a plan', $html);
        $this->assertStringContainsString('OpenAI', $html);
    }

    public function test_connector_card_renders_action(): void
    {
        $html = Blade::render('<x-nova.connector-card name="Stripe" provider="Payments" action="connectStripe" />');

        $this->assertStringContainsString('Stripe', $html);
        $this->assertStringContainsString('wire:click="connectStripe"', $html);
        $this->assertStringContainsString('Nunca sincronizado', $html);
    }

    public function test_marketplace_card_renders_included_capabilities(): void
    {
        $html = Blade::render('<x-nova.marketplace-card name="Hotel" :includes="$includes" action="install" />', [
            'includes' => ['Reservations', 'Rooms'],
        ]);

        $this->assertStringContainsString('Hotel', $html);
        $this->assertStringContainsString('Reservations', $html);
        $this->assertStringContainsString('Rooms', $html);
        $this->assertStringContainsString('wire:click="install"', $html);
    }

    public function test_search_overlay_exposes_items_and_keyboard_shortcut(): void
    {
        $html = Blade::render('<x-nova.search-overlay :items="$items" />', [
            'items' => [
                ['label' => 'Facturas', 'group' => 'Sales', 'url' => '/facturacion/facturas'],
            ],
        ]);

        $this->assertStringContainsString('keydown.window.meta.k', $html);
        $this->assertStringContainsString('Facturas', $html);
        $this->assertStringContainsString('facturacion', $html);
    }

    public function test_quick_actions_set_the_bound_property(): void
    {
        $html = Blade::render('<x-nova.quick-actions :actions="$actions" model="prompt" />', [
            'actions' => ['Plan my week'],
        ]);

        $this->assertStringContainsString('Plan my week', $html);
        $this->assertStringContainsString('$wire.set(', $html);
    }
}
