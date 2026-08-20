<?php

namespace App\Filament\Portal\Pages;

use App\Models\BookingDepartment;
use App\Models\Taxista;
use App\Support\DepartmentChatStarter;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class TaxistaChats extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat';

    protected static \UnitEnum|string|null $navigationGroup = 'Taxista';

    protected static ?string $slug = 'chat';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.portal.pages.taxista-chats';

    public ?int $departmentId = null;

    public ?string $departmentName = null;

    public static function canAccess(): bool
    {
        $user = auth('taxista')->user() ?? auth('web')->user();

        if (! $user) {
            return false;
        }

        return Taxista::query()->withoutGlobalScopes()->whereKey($user->id)->exists();
    }

    public function mount(): void
    {
        if (! $this->departmentId) {
            $department = BookingDepartment::query()
                ->chatEnabled()
                ->where(fn ($query) => $query
                    ->where('slug', 'operaciones')
                    ->orWhere('name', 'Operaciones'))
                ->orderByRaw("CASE WHEN slug = 'operaciones' THEN 0 WHEN name = 'Operaciones' THEN 1 ELSE 9 END")
                ->first(['id', 'name']);

            if (! $department) {
                $department = BookingDepartment::query()
                    ->chatEnabled()
                    ->orderBy('name')
                    ->first(['id', 'name']);
            }

            $this->departmentId = $department?->id;
            $this->departmentName = $department?->name;
        }
    }

    /**
     * @return Collection<int, BookingDepartment>
     */
    public function departments(): Collection
    {
        return BookingDepartment::query()
            ->chatEnabled()
            ->orderByRaw("CASE WHEN slug = 'operaciones' THEN 0 WHEN name = 'Operaciones' THEN 1 ELSE 9 END")
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function selectDepartment(int $departmentId): void
    {
        $department = BookingDepartment::query()
            ->chatEnabled()
            ->whereKey($departmentId)
            ->first(['id', 'name']);

        if (! $department) {
            return;
        }

        $this->departmentId = (int) $department->id;
        $this->departmentName = $department->name;
    }

    public function openChat(): void
    {
        if (! $this->departmentId) {
            return;
        }

        $conversationId = DepartmentChatStarter::start($this->departmentId);

        if (! $conversationId) {
            Notification::make()
                ->title('No se pudo iniciar el chat')
                ->body('No hay un usuario responsable disponible para este departamento.')
                ->warning()
                ->send();

            return;
        }

        if (! app('router')->has('wirechat.chats.chat')) {
            $chatUrl = $this->chatUrl();

            if (! $chatUrl) {
                return;
            }

            $this->redirect($chatUrl, navigate: false);

            return;
        }

        $this->redirect(route('wirechat.chats.chat', ['conversation' => $conversationId]), navigate: false);
    }

    public function chatUrl(): ?string
    {
        if (! app('router')->has('wirechat.chats.chats')) {
            return null;
        }

        return route('wirechat.chats.chats');
    }
}
