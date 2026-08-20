<?php

namespace App\Filament\App\Resources\Announcements\Widgets;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\CanPoll;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Marcelodelgado\Announcements\AnnouncementsPlugin;
use App\Models\Announcement;
use Illuminate\Support\HtmlString;

class AnnouncementsWidget extends Widget 
{
    use CanPoll;

    protected static ?int $sort = -4;

    protected int|string|array $columnSpan = 'full';
    protected int $userId;

    /**
     * @var view-string
     */
    protected string $view = 'filament.app.widget.announcements-widget';

    public function mount(): void
    {
        $panel = Filament::getCurrentPanel();

        $this->userId = auth()->user()->id;
        $this->role = auth()->user()->role;

        $this->pollingInterval = config('announcements.polling_interval', '60s');
    }

    
    /**
     * @return Collection<int, Announcement>
     */
    public function getAnnouncements(): Collection
    {
        $userId = auth()->user()->id;

        return Announcement::query()
            ->visibleForDashboard()
            ->orderedForDisplay()
            ->whereDoesntHave('users', function ($query) use ($userId): void {
                $query->where('users.id', $userId)
                    ->whereNotNull('announcement_user.dismissed_at');
            })
            ->get();
    }

     public function availableAnnouncements(): Collection
    {
        $departmentId = $this->department_id;
        $userId = auth()->user()->id;

        return Announcement::query()
            ->active()
            ->visibleForDashboard()
            ->orderedForDisplay()
            /*->where(function ($query) use ($departmentId) {
                $query->where('for_users', true);

                if ($departmentId) {
                    $query->orWhereHas('departments', fn ($query) => $query->where('departments.id', $departmentId));
                }

                if (in_array($this->role, ['owner', 'employee'], true)) {
                    $query->orWhere('for_clients', true);
                }
            })*/
            ->get();
    }
    public function dismiss(int $announcementId): void
    {
        $announcement = Announcement::query()->find($announcementId);

        if (! $announcement || ! $announcement->is_dismissible) {
            return;
        }

        DB::table('announcement_user')->updateOrInsert(
            [
                'announcement_id' => $announcementId,
                'user_id' => auth()->user()->id,
            ],
            ['dismissed_at' => now()],
        );
    }
}
