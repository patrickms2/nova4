<?php

namespace Tests\Unit\Community;

use App\Models\CommunityAppointment;
use App\Support\CommunityAppointmentAvailability;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommunityAppointmentAvailabilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('community_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('community_id');
            $table->string('title');
            $table->dateTime('starts_at');
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    public function test_it_exposes_half_hour_slots_and_removes_booked_times(): void
    {
        $date = $this->nextWeekday()->format('Y-m-d');
        CommunityAppointment::create(['community_id' => 10, 'title' => 'Ya reservada', 'starts_at' => $date.' 10:00', 'status' => 'confirmed']);

        $slots = app(CommunityAppointmentAvailability::class)->slots(10, $date);

        $this->assertArrayHasKey('09:00', $slots);
        $this->assertArrayNotHasKey('10:00', $slots);
        $this->assertArrayHasKey('16:30', $slots);
        $this->assertTrue(app(CommunityAppointmentAvailability::class)->isAvailable(10, $date, '09:30'));
    }

    public function test_weekends_are_not_available(): void
    {
        $saturday = CarbonImmutable::now()->next(CarbonImmutable::SATURDAY)->format('Y-m-d');

        $this->assertSame([], app(CommunityAppointmentAvailability::class)->slots(10, $saturday));
    }

    private function nextWeekday(): CarbonImmutable
    {
        $date = CarbonImmutable::tomorrow();

        while ($date->isWeekend()) {
            $date = $date->addDay();
        }

        return $date;
    }
}
