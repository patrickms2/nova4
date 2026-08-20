<?php

namespace App\Filament\Portal\Widgets;

use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Models\Note;
use App\Models\Task;
use App\Models\Taxi;
use App\Models\TaxiCentral\Document;
use App\Models\TaxiCentral\Meeting;
use App\Models\Taxi\Taxista;
use App\Models\Taxi\Ticket;
use App\Models\Informe;
use App\Filament\Portal\Pages\Appointments;
use App\Filament\Portal\Pages\Documents;
use App\Filament\Portal\Pages\Informes;
use App\Filament\Portal\Pages\Notes;
use App\Filament\Portal\Pages\Tasks;
use App\Filament\Portal\Pages\Tickets;
use App\Filament\Portal\Resources\Taxis\TaxiResource;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaExpense;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use App\Support\Portal\PortalTaxistaContext;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as DbSchema;

class TaxistaTotals extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';
    protected int|array|null $columns = [
        'default' => 2,
        'md' => 2,
        'xl' => 2,
    ];

    protected function getStats(): array
    {
        $taxistaId = PortalTaxistaContext::taxistaId();
        $portalUserId = PortalTaxistaContext::meetingCreatorUserId();

        if (!$taxistaId) {
            return [
                self::kpi('Taxis', 0, Heroicon::OutlinedTruck, 'primary', 'Ver flota', self::safeUrl(static fn (): string => TaxistaTaxiResource::getUrl('index', panel: 'portal')), 'taxis'),
                self::kpi('Citas', 0, Heroicon::OutlinedCalendarDays, 'warning', 'Próxima cita: - · Ver agenda', self::safeUrl(static fn (): string => Appointments::getUrl(panel: 'portal')), 'citas'),
                self::kpi('Tickets', 0, Heroicon::OutlinedTicket, 'danger', 'Ver incidencias', self::safeUrl(static fn (): string => Tickets::getUrl(panel: 'portal')), 'tickets'),
                self::kpi('Documentos', 0, Heroicon::PaperClip, 'info', 'Ver documentación', self::safeUrl(static fn (): string => Documents::getUrl(panel: 'portal')), 'documentos'),
                self::kpi('Notas', 0, Heroicon::OutlinedChatBubbleLeftRight, 'gray', 'Ver notas', self::safeUrl(static fn (): string => Notes::getUrl(panel: 'portal')), 'notas'),
                self::kpi('Gastos', 0, Heroicon::OutlinedCurrencyDollar, 'success', 'Ver gastos', '#', 'gastos'),
            ];
        }

        $stats = Cache::remember(
            sprintf('portal:taxista-totals:%d:%s', $taxistaId, $portalUserId ?? 'guest'),
            now()->addSeconds(20),
            static function () use ($portalUserId, $taxistaId): array {
                $nextMeeting = $portalUserId
                    ? TaxistaAppointment::query()
                        ->where('created_by_user_id', $portalUserId)
                        ->where('status', '!=', 'cancelada')
                        ->where('scheduled_start_at', '>=', Carbon::now())
                        ->orderBy('scheduled_start_at')
                        ->first()
                    : null;

                return [
                    'taxis' => TaxistaTaxi::query()->where('taxista_id', $taxistaId)->count(),
                    'citas' => $portalUserId
                        ? TaxistaAppointment::query()->where('created_by_user_id', $portalUserId)->count()
                        : 0,
                    'tickets' => TaxistaTicket::query()->where('usuario_id', $taxistaId)->count(),
                    'documentos' => TaxistaDocument::query()->where('usuario_id', $taxistaId)->count(),
                    'gastos' => TaxistaExpense::query()->where('taxista_user_id', $taxistaId)->count(),
                    'next_meeting' => $nextMeeting?->scheduled_start_at?->format('d/m/Y H:i'),
                ];
            },
        );

        $taxisCount = (int) $stats['taxis'];
        $citasCount = (int) $stats['citas'];
        $nextMeetingLabel = $nextMeeting?->scheduled_start_at
            ? 'Próxima cita: ' . $nextMeeting->scheduled_start_at->format('d/m/Y H:i')
            : 'Próxima cita: -';
        $nextMeetingLabel = !empty($stats['next_meeting'])
            ? 'Próxima cita: ' . $stats['next_meeting']
            : 'Próxima cita: -';
        $ticketsCount = (int) $stats['tickets'];
        $documentosCount = (int) $stats['documentos'];
        $gastosCount = (int) $stats['gastos'];
        //$notasCount = Note::query()->where('related_type', Taxista::class)->where('related_id', $taxistaId)->count();
        //$tareasCount = Task::query()->where('related_type', Taxista::class)->where('related_id', $taxistaId)->count();

        /*$informesCount = Informe::query()
            ->when(DbSchema::hasColumn('informes', 'taxista_id'), fn($query) => $query->where('taxista_id', $taxistaId), fn($query) => $query->whereRaw('1 = 0'))
            ->count();
*/
        return [
            self::kpi('Taxis', $taxisCount, Heroicon::OutlinedTruck, 'primary', 'Ver flota', self::safeUrl(static fn (): string => TaxiResource::getUrl('index', panel: 'portal')), 'taxis'),
            self::kpi('Citas', $citasCount, Heroicon::OutlinedCalendarDays, 'warning', $nextMeetingLabel . ' · Ver agenda', self::safeUrl(static fn (): string => Appointments::getUrl(panel: 'portal')), 'citas'),
            self::kpi('Tickets', $ticketsCount, Heroicon::OutlinedTicket, 'danger', 'Ver incidencias', self::safeUrl(static fn (): string => Tickets::getUrl(panel: 'portal')), 'tickets'),
            self::kpi('Documentos', $documentosCount, Heroicon::PaperClip, 'info', 'Ver documentación', self::safeUrl(static fn (): string => Documents::getUrl(panel: 'portal')), 'documentos'),
            //self::kpi('Notas', $notasCount, Heroicon::OutlinedChatBubbleLeftRight, 'gray', 'Ver notas', Notes::getUrl(), 'notas'),
            //self::kpi('Avisos', $tareasCount, Heroicon::OutlinedCheckCircle, 'success', 'Ver avisos', Tasks::getUrl(), 'avisos'),
            //self::kpi('Informes', $informesCount, Heroicon::OutlinedDocumentText, 'success', 'Ver informes', Informes::getUrl()),
            self::kpi('Gastos', $gastosCount, Heroicon::OutlinedCurrencyDollar, 'success', 'Ver gastos', '#', 'gastos'),
        ];
    }

    private static function kpi(string $label, int $value, string|\BackedEnum $icon, string $color, string $description, string $url, string $model): Stat
    {
        return Stat::make($label, (string)$value)
            ->icon($icon)
            ->color($color)
            ->description($description)
            ->url($url)
            ->extraAttributes([
                'class' => 'portal-kpi-card',
                'data-kpi-color' => $color,
                'data-kpi-model' => $model,
            ]);
    }

    private static function safeUrl(callable $resolver): string
    {
        try {
            return (string) $resolver();
        } catch (\Throwable) {
            return '#';
        }
    }
}
