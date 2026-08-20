<?php

namespace App\Filament\App\Resources\Attendances\Widgets;

use App\Models\Taxi\Attendance;
use Filament\Widgets\Widget;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends Widget
{
    protected string $view = 'filament.resources.calendar-widget';

    public $calendar = [];
    public $summary = [];

    public function mount($contractId, $absence)
    {
        $absences = collect([$absence])->all();
        [$this->calendar, $this->summary]
            = app(Attendance::class)->buildCalendar($contractId, $year ?? date('Y'), $absences);
    }
}
