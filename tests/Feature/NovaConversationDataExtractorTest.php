<?php

namespace Tests\Feature;

use App\Services\Nova\NovaAiService;
use App\Services\Nova\NovaConversationDataExtractor;
use App\Models\NovaIntentRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovaConversationDataExtractorTest extends TestCase
{
    use RefreshDatabase;

    private NovaConversationDataExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $mockAiService = $this->createMock(NovaAiService::class);
        $mockAiService->method('extractBookingData')->willReturn([
            'date' => null,
            'time' => null,
            'party_size' => null,
            'customer_name' => null,
            'customer_phone' => null,
            'preferences' => null,
        ]);
        $mockAiService->method('detectIntent')->willReturn([
            'intent' => 'unknown',
            'confidence' => 0.0,
            'reasoning' => null,
        ]);

        $this->extractor = new NovaConversationDataExtractor($mockAiService);
    }

    public function test_extract_contact_details_with_name_and_phone(): void
    {
        $result = $this->extractor->extract('Patrick 646427442', '+34646426442', [
            'stage' => 'awaiting_customer_name',
            'intent' => 'winery_visit',
            'date' => ['label' => 'mañana', 'value' => '2026-05-26'],
            'time' => ['label' => '13:00', 'value' => '13:00'],
            'party_size' => 2,
        ]);

        $this->assertEquals('Patrick', $result['customer_name']);
        $this->assertEquals('646427442', $result['customer_phone']);
        $this->assertEquals('contact_details_received', $result['stage']);
        $this->assertEquals('winery_visit', $result['intent']);
        $this->assertEquals(2, $result['party_size']);
    }

    public function test_extract_contact_details_with_email(): void
    {
        $result = $this->extractor->extract('Maria patrick@example.com', '+34646426442', [
            'stage' => 'awaiting_customer_name',
            'intent' => 'winery_visit',
            'date' => ['label' => 'mañana', 'value' => '2026-05-26'],
            'time' => ['label' => '13:00', 'value' => '13:00'],
            'party_size' => 2,
        ]);

        $this->assertEquals('Maria', $result['customer_name']);
        $this->assertEquals('patrick@example.com', $result['customer_phone']);
        $this->assertEquals('contact_details_received', $result['stage']);
    }

    public function test_extract_contact_details_preserves_booking_context(): void
    {
        $result = $this->extractor->extract('Juan 611123456', '+34646426442', [
            'stage' => 'awaiting_customer_name',
            'intent' => 'winery_visit',
            'date' => ['label' => 'mañana', 'value' => '2026-05-26'],
            'time' => ['label' => '11:00', 'value' => '11:00'],
            'party_size' => 3,
        ]);

        $this->assertEquals('Juan', $result['customer_name']);
        $this->assertEquals('611123456', $result['customer_phone']);
        $this->assertEquals('contact_details_received', $result['stage']);
        // Ensure booking details are preserved
        $this->assertEquals(3, $result['party_size']);
        $this->assertEquals('11:00', $result['time']['label']);
        $this->assertEquals('mañana', $result['date']['label']);
    }

    public function test_extract_contact_details_from_complete_message(): void
    {
        $result = $this->extractor->extract('reserva visita guiada mañana 2 personas Patrick 646427442', '+34646426442', null);

        $this->assertEquals('Patrick', $result['customer_name']);
        $this->assertEquals('646427442', $result['customer_phone']);
        // Message contains both "reserva" and "visita", so it detects as restaurant_and_winery_visit
        $this->assertEquals('restaurant_and_winery_visit', $result['intent']);
        $this->assertEquals(2, $result['party_size']);
        $this->assertEquals('mañana', $result['date_label']);
        // Should be collecting details since time is missing
        $this->assertEquals('collecting_booking_details', $result['stage']);
    }

    public function test_preserve_contact_details_when_selecting_time(): void
    {
        // First message with all details except time
        $firstResult = $this->extractor->extract('visita guiada mañana 2 personas Patrick 646427442', '+34646426442', null);

        // Second message selecting time (simulating user clicking a time button)
        // Include context to preserve date and party size
        $secondResult = $this->extractor->extract('16:00', '+34646426442', $firstResult);

        $this->assertEquals('Patrick', $secondResult['customer_name']);
        $this->assertEquals('646427442', $secondResult['customer_phone']);
        $this->assertEquals('16:00', $secondResult['time_label']);
        $this->assertEquals('ready_to_confirm', $secondResult['stage']);
        // Ensure other details are preserved
        $this->assertEquals(2, $secondResult['party_size']);
        $this->assertEquals('mañana', $secondResult['date_label']);
    }

    public function test_is_simple_interaction_detection(): void
    {
        // Simple selection
        $result = $this->extractor->extract('1', '+34646426442', null);
        $this->assertTrue($result['is_simple']);

        // Time slot click
        $result = $this->extractor->extract('11:30', '+34646426442', null);
        $this->assertTrue($result['is_simple']);

        // Button click
        $result = $this->extractor->extract('Aportar datos', '+34646426442', null);
        $this->assertTrue($result['is_simple']);

        // Complex sentence
        $result = $this->extractor->extract('Quiero una reserva para la bodega mañana por la mañana', '+34646426442', null);
        $this->assertFalse($result['is_simple']);
    }

    public function test_numeric_selection_after_commercial_suggestions_uses_previous_menu_context(): void
    {
        $result = $this->extractor->extract('4', '+340000001', [
            'stage' => 'answering_commercial_info',
            'intent' => 'commercial_info',
        ]);

        $this->assertEquals('restaurant_booking', $result['intent']);
        $this->assertEquals('collecting_booking_details', $result['stage']);
        $this->assertContains('día', $result['missing_labels']);
        $this->assertContains('hora', $result['missing_labels']);
        $this->assertContains('número de personas', $result['missing_labels']);
    }

    public function test_numeric_selection_without_menu_context_keeps_legacy_taxi_mapping(): void
    {
        $result = $this->extractor->extract('4', '+340000001', null);

        $this->assertEquals('taxi_booking', $result['intent']);
    }

    public function test_affirmative_reply_confirms_ready_taxi_booking(): void
    {
        $result = $this->extractor->extract('correcto', '+340000001', [
            'stage' => 'ready_to_confirm',
            'intent' => 'taxi_booking',
            'date' => ['label' => 'mañana', 'value' => '2026-06-06'],
            'time' => ['label' => '12:00', 'value' => '12:00'],
            'party_size' => 4,
            'origin' => 'Puerto del Carmen',
            'destination' => 'Bodega La Geria',
        ]);

        $this->assertEquals('taxi_booking', $result['intent']);
        $this->assertEquals('booking_confirmed', $result['stage']);
        $this->assertEquals('Puerto del Carmen', $result['origin']);
        $this->assertEquals('Bodega La Geria', $result['destination']);
        $this->assertEquals(4, $result['party_size']);
    }

    public function test_numeric_confirmation_uses_ready_to_confirm_context(): void
    {
        $result = $this->extractor->extract('1', '+340000001', [
            'stage' => 'ready_to_confirm',
            'intent' => 'winery_visit',
            'date' => ['label' => 'mañana', 'value' => '2026-06-06', 'weekday' => 'Sábado'],
            'time' => ['label' => '11:00', 'value' => '11:00'],
            'party_size' => 2,
            'customer_name' => 'Patrick',
            'customer_phone' => '646426442',
        ]);

        $this->assertEquals('winery_visit', $result['intent']);
        $this->assertEquals('booking_confirmed', $result['stage']);
        $this->assertEquals('confirm_booking', $result['quick_reply_action']);
        $this->assertEquals('mañana', $result['date_label']);
    }

    public function test_cross_sell_taxi_selection_preserves_visit_context(): void
    {
        $result = $this->extractor->extract('2', '+340000001', [
            'stage' => 'ready_to_confirm',
            'intent' => 'winery_visit',
            'business' => 'Bodega La Geria',
            'date' => ['label' => 'mañana', 'value' => '2026-06-06', 'weekday' => 'Sábado'],
            'time' => ['label' => '11:00', 'value' => '11:00'],
            'party_size' => 2,
        ]);

        $this->assertEquals('taxi_booking', $result['intent']);
        $this->assertEquals('collecting_taxi_details', $result['stage']);
        $this->assertEquals('add_taxi', $result['quick_reply_action']);
        $this->assertEquals('mañana', $result['date_label']);
        $this->assertEquals('11:00', $result['time_label']);
        $this->assertEquals(2, $result['party_size']);
        $this->assertEquals('Bodega La Geria', $result['destination']);
        $this->assertContains('origen', $result['missing_labels']);
    }

    public function test_configured_filament_intent_rule_is_used_before_hardcoded_fallback(): void
    {
        NovaIntentRule::query()->create([
            'intent_key' => 'route_recommendation',
            'rule_type' => 'include',
            'keywords' => ['senderismo secreto'],
            'description' => 'Test configurable route intent',
            'priority' => 0,
            'is_active' => true,
        ]);

        $result = $this->extractor->extract('quiero senderismo secreto para mañana', '+340000001', null);

        $this->assertEquals('route_recommendation', $result['intent']);
        $this->assertEquals('selecting_intent', $result['stage']);
    }
}
