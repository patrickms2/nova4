<?php

namespace App\Filament\App\Pages;

use App\Models\BookingDepartment;
use App\Support\DepartmentChatStarter;
use App\Support\DepartmentManagerAccess;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class DepartmentChats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chats';

    protected static \UnitEnum|string|null $navigationGroup = 'Soporte';

    protected static ?string $slug = 'chat';

    protected static ?int $navigationSort = 11;

    public static function shouldRegisterNavigation(): bool
    {
        return DepartmentManagerAccess::canAccessService('has_chats_service');
    }

    protected string $view = 'filament.app.pages.department-chats';

    public ?int $departmentId = null;

    public function mount(): void
    {
        if ($this->departmentId && DepartmentManagerAccess::canAccessDepartment($this->departmentId)) {
            return;
        }

        $this->departmentId = $this->departmentQuery()->value('id');
    }

    /**
     * @return Collection<int, BookingDepartment>
     */
    public function departments(): Collection
    {
        return $this->departmentQuery()
            ->withCount(['calendars as active_chats_count' => function ($query) {
                // Esta es una aproximación, ya que no tenemos una relación directa con conversaciones.
                // Usamos la lógica de búsqueda por tag en mensajes para contar.
            }])
            ->get(['id', 'name', 'color']);
    }

    public function getActiveConversations(int $departmentId): \Illuminate\Support\Collection
    {
        $department = BookingDepartment::find($departmentId);
        if (!$department) return collect();

        $searchString = sprintf('[%s]', $department->name);

        return \AdultDate\FilamentWirechat\Models\Conversation::query()
            ->whereHas('messages', function ($query) use ($searchString) {
                $query->where('body', 'like', '%' . $searchString . '%');
            })
            ->with(['participants.participantable', 'lastMessage'])
            ->latest('updated_at')
            ->get()
            ->map(function ($conversation) {
                $taxista = $conversation->participants
                    ->map(fn($p) => $p->participantable)
                    ->first(fn($m) => ($m instanceof \App\Models\User || $m instanceof \App\Models\Taxista) && ($m->role === 'taxista' || $m->role === 'service'));

                return [
                    'id' => $conversation->id,
                    'taxista_name' => $taxista?->name ?? 'Desconocido',
                    'last_message' => $conversation->lastMessage?->body,
                    'updated_at' => $conversation->updated_at->diffForHumans(),
                    'is_participant' => $conversation->participants->where('participantable_id', auth()->id())->isNotEmpty(),
                ];
            });
    }

    public function joinChat(string $conversationId): void
    {
        $conversation = \AdultDate\FilamentWirechat\Models\Conversation::find($conversationId);
        if (!$conversation) return;

        $user = auth()->user();

        if (!$user) {
            return;
        }

        // Verificar si ya es participante
        if (!$conversation->participants()
            ->where('participantable_id', $user->id)
            ->where('participantable_type', $user->getMorphClass())
            ->exists()
        ) {
            $conversation->addParticipant($user, \AdultDate\FilamentWirechat\Enums\ParticipantRole::ADMIN);

            $user->sendMessageTo(
                $conversation,
                sprintf('[%s] El administrador %s se ha unido al chat.', 'Soporte', $user->name)
            );
        }

        $this->redirect(route('wirechat.chats.chat', ['conversation' => $conversationId]), navigate: true);
    }

    public function selectDepartment(int $departmentId): void
    {
        if (! DepartmentManagerAccess::canAccessDepartment($departmentId)) {
            return;
        }

        $this->departmentId = $departmentId;
    }

    public function openChat(): void
    {
        if (!app('router')->has('wirechat.chats.chats')) {
            return;
        }

        $this->redirect(route('wirechat.chats.chats'), navigate: true);
    }

    public function chatUrl(): ?string
    {
        if (!app('router')->has('wirechat.chats.chats')) {
            return null;
        }

        return route('wirechat.chats.chats');
    }

    private function departmentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = BookingDepartment::query()
            ->chatEnabled()
            ->orderBy('name');

        return DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_chats_service');
    }
}
