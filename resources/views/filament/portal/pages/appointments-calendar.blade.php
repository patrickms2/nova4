<x-filament-panels::page>
    @php($calendar = $this->getCalendarPayload())

    <div class="portal-calendar-nav">
        <a href="{{ \App\Filament\Portal\Pages\AppointmentsCalendar::getUrl(['month' => $calendar['prevMonth']]) }}" class="portal-calendar-nav__btn">
            ‹
        </a>
        <h3>{{ $this->monthLabel() }}</h3>
        <a href="{{ \App\Filament\Portal\Pages\AppointmentsCalendar::getUrl(['month' => $calendar['nextMonth']]) }}" class="portal-calendar-nav__btn">
            ›
        </a>
    </div>

    <div class="portal-calendar-grid portal-calendar-grid--head">
        @foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $day)
            <div class="portal-calendar-head">{{ $day }}</div>
        @endforeach
    </div>

    <div class="portal-calendar-grid">
        @foreach ($calendar['weeks'] as $week)
            @foreach ($week as $day)
                <div class="portal-calendar-day {{ $day['inMonth'] ? '' : 'portal-calendar-day--muted' }}">
                    <div class="portal-calendar-day__date">{{ $day['date']->format('j') }}</div>

                    @foreach ($day['items']->take(3) as $meeting)
                        <div class="portal-calendar-event portal-calendar-event--{{ $meeting->status ?? 'pendiente' }}">
                            {{ $meeting->scheduled_start_at?->format('H:i') }} · {{ $meeting->title ?: 'Cita' }}
                        </div>
                    @endforeach

                    @if ($day['items']->count() > 3)
                        <div class="portal-calendar-more">+{{ $day['items']->count() - 3 }} más</div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>

