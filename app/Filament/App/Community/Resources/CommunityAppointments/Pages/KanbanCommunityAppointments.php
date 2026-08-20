<?php

namespace App\Filament\App\Community\Resources\CommunityAppointments\Pages;

use App\Filament\App\Community\Resources\CommunityAppointments\CommunityAppointmentResource;
use App\Models\CommunityAppointment;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;

class KanbanCommunityAppointments extends Page
{
    protected static string $resource = CommunityAppointmentResource::class;

    protected string $view = 'filament.app.community.appointments.kanban';

    protected static ?string $title = 'Kanban de citas';

    public function columns(): array
    {
        return collect(['scheduled' => 'Pendientes', 'confirmed' => 'Confirmadas', 'completed' => 'Finalizadas', 'cancelled' => 'Canceladas'])->map(fn (string $label, string $status): array => ['status' => $status, 'label' => $label, 'items' => CommunityAppointment::with(['person', 'community'])->where('status', $status)->orderBy('starts_at')->limit(100)->get()])->values()->all();
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('table')->label('Tabla')->url(CommunityAppointmentResource::getUrl()), Action::make('calendar')->label('Calendario')->url(CommunityAppointmentResource::getUrl('calendar'))];
    }
}
