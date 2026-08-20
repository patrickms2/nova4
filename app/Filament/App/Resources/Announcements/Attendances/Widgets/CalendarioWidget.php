<?php

namespace App\Filament\App\Resources\Attendances\Widgets;

use App\Models\Taxi\Attendance;
use App\Models\Taxi\Cita;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Widgets\Widget;
use \Guava\Calendar\Filament\CalendarWidget;
use App\Filament\App\Resources\Attendances\Attendances\AttendanceResource;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Guava\Calendar\Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Guava\Calendar\Enums\CalendarViewType;

class CalendarioWidget extends CalendarWidget
{
    public string|null|\Illuminate\Database\Eloquent\Model $model = Attendance::class;
    protected static ?int $sort = 5;
    protected bool $eventClickEnabled = true;
    protected bool $eventResizeEnabled = false;
    protected bool $eventDragEnabled = true;
    protected bool $noEventsClickEnabled = true;
    protected bool $dateClickEnabled = true;
    protected ?string $defaultEventClickAction = 'edit';
    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;
    protected bool $dayMaxEvents = false;
    protected string|bool|null|HtmlString $heading = 'Calendario de Citas';

    protected static ?string $title = 'Calendario de Citas';


    public function createEventAction(): CreateAction
    {
        return $this->createAction(Cita::class)
            ->label('New')
            ->icon('heroicon-o-plus')
            ->schema([
                TextInput::make('title')
                    ->required(),
                Textarea::make('description'),
                Grid::make(2)
                    ->schema([
                        Select::make('type')
                            ->options([
                                'meeting' => 'Meeting',
                                'appointment' => 'Appointment',
                                'deadline' => 'Deadline',
                                'event' => 'Event'
                            ]),
                        Toggle::make('all_day')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {

                                if ($state) {
                                    $set('start_time', now()->startOfDay());
                                    $set('end_time', now()->endOfDay());

                                }
                            })
                            ->label('All day'),

                    ]),
                Grid::make(2)
                    ->schema([
                        DateTimePicker::make('start_time')
                            ->required()
                            ->readOnly(fn($get) => $get('all_day'))

                        ,
                        DateTimePicker::make('end_time')
                            ->required()
                            ->readOnly(fn($get) => $get('all_day'))

                    ])


            ]);
    }

    protected function getEvents(FetchInfo $fetchInfo): Collection|array|Builder
    {
        $citas = Attendance::query()
            ->where('startDate', '>=', $fetchInfo['start'])
            ->where('endDate', '<=', $fetchInfo['end'])
            ->get()
            ->map(
                fn(Attendance $event) => [
                    'title' => $event->id,
                    'start' => $event->startDate,
                    'end' => $event->endDate,
                    'url' => AttendanceResource::getUrl(name: 'view', parameters: ['record' => $event]),
                    'shouldOpenUrlInNewTab' => true
                ]
            )
            ->all();

        return $citas;

    }

    public function config(): array
    {
        return [
            'firstDay' => 1,
            'headerToolbar' => [
                'left' => 'dayGridWeek,dayGridDay',
                'center' => 'title',
                'right' => 'prev,next today',
            ],
        ];
    }
}
