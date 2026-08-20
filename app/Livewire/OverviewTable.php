<?php

namespace App\Livewire\Overview;

use App\Models\Idea;
use App\Models\IdeaVote;
use App\Models\IdeaStatus;
use App\Notifications\StatusChangeNotification;
use Livewire\Component;
use App\Services\UserProductFilterService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OverviewTable extends Component
{
    // Number of records to load per page (initial load and infinite scroll)
    const RECORDS_PER_PAGE = 15;

    public $categoryFilter = [];
    public $productFilter;
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $statuses;
    public $addVoteManger = false;

    // Data properties for cursor-based pagination
    public $loadedIdeas = []; // Array to store loaded ideas for each status
    public $statusTotalCounts = []; // Array to store total counts for each status
    public $statusLastIdeaIds = []; // Last idea ID per status for cursor pagination
    public $statusLoadedIdeaIds = []; // Track loaded IDs per status to prevent duplicates
    public $statusHasMore = []; // Track if more ideas available per status
    public $statusLoading = []; // Track loading state per status

    protected ?UserProductFilterService $filterService = null;
    public bool $voteUpdateInProgress = false;

    protected $listeners = [
        'refreshProduct' => '$refresh',
        'updateStatusOrder' => 'updateStatusOrder',
        'productFilterUpdated' => 'updateProductFilter',
        'updateIdeaStatus' => 'updateIdeaStatus',
        'ideas-merged' => '$refresh',
    ];

    protected $queryString = ['categoryFilter'];

    public function mount(UserProductFilterService $filterService)
    {
        $this->filterService = $filterService;
        $this->productFilter = $this->ensureFilterService()->getSelectedProductId();
        $this->loadStatuses();
    }

    /**
     * Ensure the filter service is available on every Livewire request.
     * Livewire does not re-run mount() on subsequent requests, so we
     * need to lazily resolve the service when needed.
     */
    protected function ensureFilterService(): UserProductFilterService
    {
        if (!$this->filterService) {
            $this->filterService = app(UserProductFilterService::class);
        }

        return $this->filterService;
    }

    public function updateProductFilter($productId)
    {
        $this->productFilter = $productId;
        $this->ensureFilterService()->setSelectedProductId($productId);

        // Reset pagination when product changes
        $this->resetPagination();
        $this->loadStatuses();
        $this->dispatch('$refresh');
    }

    protected function loadStatuses()
    {
        $productId = $this->productFilter;

        $this->statuses = IdeaStatus::where('company_id', Auth::user()->company_id)
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->orderBy('order_no')
            ->get();

        // Initialize pagination data for each status
        $this->resetPagination();

        // Calculate and cache total counts for each status
        $this->calculateStatusCounts();

        // Load initial records per status (cursor-based pagination)
        $this->loadStatusIdeas();
    }

    protected function resetPagination()
    {
        $this->statusLastIdeaIds = [];
        $this->loadedIdeas = [];
        $this->statusLoadedIdeaIds = [];
        $this->statusHasMore = [];
        $this->statusLoading = [];
        $this->statusTotalCounts = [];
    }

    protected function calculateStatusCounts()
    {
        $productId = $this->productFilter;
        $statusIds = $this->statuses->pluck('id')->toArray();

        if (empty($statusIds)) {
            $this->statusTotalCounts = [];
            return;
        }

        // Build query for counting
        $query = Idea::whereNull('merged_to_id')
            ->where('archived', false)
            ->where('is_locked', 0)
            ->whereIn('idea_status_id', $statusIds);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if (!empty($this->categoryFilter)) {
            $query->whereIn('category_id', $this->categoryFilter);
        }

        $countsQuery = $query->selectRaw('idea_status_id, COUNT(*) as count')
            ->groupBy('idea_status_id')
            ->pluck('count', 'idea_status_id')
            ->toArray();

        // Initialize status counts and pagination state
        foreach ($this->statuses as $status) {
            $this->statusTotalCounts[$status->id] = $countsQuery[$status->id] ?? 0;
            $this->statusHasMore[$status->id] = $this->statusTotalCounts[$status->id] > 0;

            // Initialize arrays if not exists
            if (!isset($this->loadedIdeas[$status->id])) {
                $this->loadedIdeas[$status->id] = [];
            }
            if (!isset($this->statusLoadedIdeaIds[$status->id])) {
                $this->statusLoadedIdeaIds[$status->id] = [];
            }
            if (!isset($this->statusLoading[$status->id])) {
                $this->statusLoading[$status->id] = false;
            }
        }
    }

    /**
     * Load initial ideas for all statuses
     */
    protected function loadStatusIdeas()
    {
        $allIdeaIds = [];

        foreach ($this->statuses as $status) {
            if (!isset($this->loadedIdeas[$status->id]) || empty($this->loadedIdeas[$status->id])) {
                $this->loadNextBatchForStatus($status->id);
            }

            // Collect idea IDs for vote count optimization
            $ideaIds = array_column($this->loadedIdeas[$status->id] ?? [], 'id');
            $allIdeaIds = array_merge($allIdeaIds, $ideaIds);
        }

        // Vote counts are now loaded via withCount() in loadNextBatchForStatus() (same as IdeaTable)
        // This is more efficient than separate preloadVoteCounts() calls
    }

    /**
     * Load next batch of ideas for a specific status using cursor-based pagination
     */
    protected function loadNextBatchForStatus($statusId)
    {
        $productId = $this->productFilter;
        $status = $this->statuses->firstWhere('id', $statusId);

        if (!$status) {
            return;
        }

        // Build base query
        $query = $this->buildIdeasQuery($status, $productId);

        // Apply cursor condition (cursor pagination, no OFFSET)
        if (isset($this->statusLastIdeaIds[$statusId]) && $this->statusLastIdeaIds[$statusId] !== null) {
            $query->where('id', '<', $this->statusLastIdeaIds[$statusId]);
        }

        // Sort by id descending (most recent first)
        $query->orderBy('id', 'desc');

        // Load only the next batch with vote counts (using withCount like IdeaTable for efficiency)
        $newIdeas = $query->select([
                'id', 'title', 'description', 'is_public', 'allow_votes',
                'idea_status_id', 'category_id', 'user_id', 'updated_at', 'created_at'
            ])
            ->with(['user:id,name', 'category:id,name'])
            ->withCount('votes as votes_count') // Load vote counts efficiently in same query (like IdeaTable)
            ->limit(self::RECORDS_PER_PAGE)
            ->get();

        // Early return if no new ideas
        if ($newIdeas->isEmpty()) {
            $this->statusHasMore[$statusId] = false;
            return;
        }

        // Extract IDs for duplicate check
        $newIdeaIds = $newIdeas->pluck('id')->toArray();

        // Fast duplicate check
        $loadedIdsMap = isset($this->statusLoadedIdeaIds[$statusId])
            ? array_flip($this->statusLoadedIdeaIds[$statusId])
            : [];
        $uniqueNewIds = [];

        foreach ($newIdeaIds as $id) {
            if (!isset($loadedIdsMap[$id])) {
                $uniqueNewIds[] = $id;
                if (!isset($this->statusLoadedIdeaIds[$statusId])) {
                    $this->statusLoadedIdeaIds[$statusId] = [];
                }
                $this->statusLoadedIdeaIds[$statusId][] = $id;
            }
        }

        // Filter to only unique ideas
        $uniqueNewIdeas = $newIdeas->filter(function ($idea) use ($uniqueNewIds) {
            return in_array($idea->id, $uniqueNewIds);
        });

        // Convert to array and include vote count from withCount()
        $ideasArray = $uniqueNewIdeas->map(function ($idea) {
            $ideaArray = $idea->toArray();
            // Include votes_count from withCount() in the array
            $ideaArray['votes_count'] = $idea->votes_count ?? 0;
            return $ideaArray;
        })->toArray();

        // Add to loaded ideas array
        if (!isset($this->loadedIdeas[$statusId])) {
            $this->loadedIdeas[$statusId] = [];
        }
        $this->loadedIdeas[$statusId] = array_merge(
            $this->loadedIdeas[$statusId],
            $ideasArray
        );

        // Update cursor and hasMore flag
        $lastIdea = $uniqueNewIdeas->last();
        if ($lastIdea) {
            $this->statusLastIdeaIds[$statusId] = $lastIdea->id;
        }

        // Check if there are more ideas
        $this->statusHasMore[$statusId] = $uniqueNewIdeas->count() >= self::RECORDS_PER_PAGE;

        // Cache vote counts for newly loaded ideas (votes_count already loaded via withCount())
        if ($uniqueNewIdeas->isNotEmpty()) {
            $cacheData = [];
            foreach ($uniqueNewIdeas as $idea) {
                $voteCount = $idea->votes_count ?? 0;
                $cacheData["idea_vote_count_{$idea->id}"] = $voteCount;
            }

            // Batch cache put operations if possible
            if (method_exists(cache()->getStore(), 'putMany')) {
                cache()->putMany($cacheData, now()->addMinutes(5));
            } else {
                foreach ($cacheData as $key => $value) {
                    cache()->put($key, $value, now()->addMinutes(5));
                }
            }
        }
    }

    public function openVoteManager($ideaId)
    {
        // Skip re-rendering to avoid blocking modal opening (same pattern as IdeaTable)
        $this->skipRender();
        $this->addVoteManger = true;
        $this->dispatch('open-vote-manager', ['ideaId' => $ideaId]);
    }

    #[On('mark-vote-update-started')]
    public function handleStart($ideaId)
    {


        try {
            cache()->put("vote_update_in_progress_{$ideaId}", true, now()->addMinutes(10));
        } catch (\Throwable $e) {
            Log::error("❌ Exception in handleStart($ideaId): " . $e->getMessage());
        }
    }

    public function markVoteUpdateCompleted($ideaId)
    {

        cache()->forget("vote_update_in_progress_{$ideaId}");
    }

    /**
     * Build the base query for ideas filtered by status, category, and product
     * This method eliminates duplicate query building code
     */
    /**
     * Build the base query for ideas filtered by status, category, and product
     * Optimized to use existing database indexes efficiently
     */
    protected function buildIdeasQuery(IdeaStatus $status, $productId)
    {
        // Start with Idea model directly to have better control over query order
        // This helps MySQL use existing composite indexes more efficiently
        $query = Idea::where('idea_status_id', $status->id)
            ->whereNull('merged_to_id')
            ->where('archived', false)
            ->where('is_locked', 0);

        // Add product_id filter early to use composite indexes (idx_ideas_category_filter, etc.)
        if ($productId) {
            $query->where('product_id', $productId);
        }

        // Add category filter if present
        if (!empty($this->categoryFilter)) {
            $query->whereIn('category_id', $this->categoryFilter);
        }

        return $query;
    }

    /**
     * Pre-load vote counts for multiple ideas to avoid N+1 queries
     * Optimized to use single batch query with proper indexing
     */
    protected function preloadVoteCounts(array $ideaIds)
    {
        if (empty($ideaIds)) {
            return;
        }

        // Remove duplicates and ensure we have valid IDs
        $ideaIds = array_unique(array_filter($ideaIds));

        if (empty($ideaIds)) {
            return;
        }

        // Get all vote counts in a single batch query (much faster than individual queries)
        // Using selectRaw with COUNT and GROUP BY leverages database indexes efficiently
        $voteCounts = IdeaVote::whereIn('idea_id', $ideaIds)
            ->selectRaw('idea_id, COUNT(*) as vote_count')
            ->groupBy('idea_id')
            ->pluck('vote_count', 'idea_id')
            ->toArray();

        // Store all vote counts in cache at once (including 0 for ideas with no votes)
        $cacheData = [];
        foreach ($ideaIds as $ideaId) {
            $count = $voteCounts[$ideaId] ?? 0;
            $cacheData["idea_vote_count_{$ideaId}"] = $count;
        }

        // Batch cache put operations if possible, otherwise do individually
        if (method_exists(cache()->getStore(), 'putMany')) {
            cache()->putMany($cacheData, now()->addMinutes(5));
        } else {
            foreach ($cacheData as $key => $value) {
                cache()->put($key, $value, now()->addMinutes(5));
            }
        }
    }

    public function getVoteCount($ideaId)
    {
        // Try to get from cache first
        $cached = cache()->get("idea_vote_count_{$ideaId}");
        if ($cached !== null) {
            return $cached;
        }

        // Fallback to database query if not cached
        $count = IdeaVote::where('idea_id', $ideaId)->count();
        cache()->put("idea_vote_count_{$ideaId}", $count, now()->addMinutes(5));
        return $count;
    }

    #[On('hideAddVote')]
    public function hideAddVote()
    {
        $this->addVoteManger = false;

    }

    public function updateIdeaStatus($ideaId, $statusId)
    {
        // Get old status ID from loaded arrays (no query - instant)
        $oldStatusId = $this->getIdeaStatusId($ideaId);

        // Only proceed if we found the idea in loaded arrays (avoids queries)
        // If not found, the database update will still happen, but UI won't update until next render
        if ($oldStatusId) {
            // Optimistically update the UI arrays FIRST (instant visual feedback)
            $this->moveIdeaBetweenStatuses($ideaId, $oldStatusId, $statusId);
        }

        // Fast direct database update (no model loading overhead)
        // This is the only query that runs - very fast
        Idea::where('id', $ideaId)->update(['idea_status_id' => $statusId]);

        // Queue email notification in background (non-blocking)
        dispatch(function () use ($ideaId, $statusId, $oldStatusId) {
            $idea = Idea::with('user')->find($ideaId);
            if ($idea && $idea->user_id) {
                try {
                    // Load statuses from database (closure can't access $this)
                    $oldStatus = $oldStatusId ? IdeaStatus::find($oldStatusId) : null;
                    $newStatus = IdeaStatus::find($statusId);

                    if ($idea->user && $newStatus) {
                        $idea->user->notify(new StatusChangeNotification($idea, $oldStatus, $newStatus));
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send status change notification: ' . $e->getMessage(), [
                        'idea_id' => $ideaId,
                        'old_status' => $oldStatusId,
                        'new_status' => $statusId,
                    ]);
                }
            }
        })->afterResponse();

        // Dispatch event - arrays are already updated optimistically
        // Livewire will re-render, but since arrays are already updated, it will be fast
        $this->dispatch('ideaStatusUpdated');
    }

    /**
     * Get idea status ID from loaded ideas array (fast) or database (fallback)
     * Optimized to avoid queries when possible
     */
    protected function getIdeaStatusId($ideaId)
    {
        // Try to find in loaded ideas first (instant, no query)
        foreach ($this->loadedIdeas as $statusId => $ideas) {
            foreach ($ideas as $idea) {
                if (($idea['id'] ?? null) == $ideaId) {
                    return $statusId;
                }
            }
        }

        // Don't query database here - if idea not in loaded arrays, return null
        // This prevents slow queries during swap operations
        // The idea should always be in loaded arrays since we're swapping displayed items
        return null;
    }

    /**
     * Move idea between status arrays instantly without reloading all data
     */
    protected function moveIdeaBetweenStatuses($ideaId, $oldStatusId, $newStatusId)
    {
        // Find the idea in the old status array
        $ideaData = null;
        $oldStatusIdeas = $this->loadedIdeas[$oldStatusId] ?? [];

        foreach ($oldStatusIdeas as $index => $idea) {
            if (($idea['id'] ?? null) == $ideaId) {
                $ideaData = $idea;
                // Remove from old status
                unset($oldStatusIdeas[$index]);
                $this->loadedIdeas[$oldStatusId] = array_values($oldStatusIdeas);
                break;
            }
        }

        // If idea not found in loaded ideas, skip the move
        // This prevents queries during swap - the idea should always be in loaded arrays
        // since we're swapping displayed items. If not found, the database update still happens
        // and the UI will sync on the next render
        if (!$ideaData) {
            return; // Skip move - no query, just return
        }

        // Update the idea's status_id in the data
        $ideaData['idea_status_id'] = $newStatusId;

        // Add to new status array at the beginning (most recent)
        $newStatusIdeas = $this->loadedIdeas[$newStatusId] ?? [];
        array_unshift($newStatusIdeas, $ideaData);
        $this->loadedIdeas[$newStatusId] = $newStatusIdeas;

        // Update counts
        if (isset($this->statusTotalCounts[$oldStatusId])) {
            $this->statusTotalCounts[$oldStatusId] = max(0, ($this->statusTotalCounts[$oldStatusId] ?? 0) - 1);
        }
        if (isset($this->statusTotalCounts[$newStatusId])) {
            $this->statusTotalCounts[$newStatusId] = ($this->statusTotalCounts[$newStatusId] ?? 0) + 1;
        }
    }

    public function toggleIdeaVisibility($ideaId)
    {
        // Get current value first
        $idea = Idea::select('is_public')->find($ideaId);
        if (!$idea) {
            return;
        }

        // Update in database
        $newValue = !$idea->is_public;
        Idea::where('id', $ideaId)->update(['is_public' => $newValue]);

        // Update in loaded ideas array to reflect change immediately without reloading
        foreach ($this->loadedIdeas as $statusId => &$ideas) {
            foreach ($ideas as &$idea) {
                if (($idea['id'] ?? null) == $ideaId) {
                    $idea['is_public'] = $newValue;
                    break 2;
                }
            }
        }
    }


    /**
     * Calculate text color based on background color brightness
     */
    protected function calculateTextColor($hexColor)
    {
        $hex = str_replace('#', '', $hexColor);

        if (strlen($hex) !== 6) {
            return '#000000';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

        return $brightness > 194 ? '#000000' : '#ffffff';
    }

    public function render()
    {
        $this->dispatch('reInitSortable');

        $this->voteUpdateInProgress = cache()->get('vote_update_in_progress', false);
        $voteUpdateMap = [];
        $voteCounts = [];
        $statusTextColors = [];

        // Pre-calculate text colors for each status
        foreach ($this->statuses as $status) {
            $statusTextColors[$status->id] = $this->calculateTextColor($status->color);
        }

        // Build vote update map and get vote counts (use votes_count from loaded ideas like IdeaTable)
        $ideasNeedingFreshCounts = [];

        foreach ($this->loadedIdeas as $statusId => $ideas) {
            foreach ($ideas as $idea) {
                $ideaId = $idea['id'] ?? null;
                if ($ideaId) {
                    $voteUpdateMap[$ideaId] = cache()->get("vote_update_in_progress_{$ideaId}", false);

                    // Use votes_count from loaded ideas (loaded via withCount() - same as IdeaTable)
                    // This is faster than cache lookups since it's already in memory
                    if (isset($idea['votes_count'])) {
                        $voteCounts[$ideaId] = $idea['votes_count'];
                    } else {
                        // Fallback to cache if votes_count not in array
                        $voteCounts[$ideaId] = cache()->get("idea_vote_count_{$ideaId}", 0);
                    }

                    // Collect idea IDs that need fresh vote counts (vote update in progress for polling)
                    if ($voteUpdateMap[$ideaId]) {
                        $ideasNeedingFreshCounts[] = $ideaId;
                    }
                }
            }
        }

        // Batch query vote counts for ideas with updates in progress (for real-time polling)
        // This is only for ideas actively being updated, not all ideas
        if (!empty($ideasNeedingFreshCounts)) {
            $freshVoteCounts = IdeaVote::whereIn('idea_id', $ideasNeedingFreshCounts)
                ->selectRaw('idea_id, COUNT(*) as vote_count')
                ->groupBy('idea_id')
                ->pluck('vote_count', 'idea_id')
                ->toArray();

            // Update voteCounts array and cache for ideas with fresh counts
            foreach ($ideasNeedingFreshCounts as $ideaId) {
                $count = $freshVoteCounts[$ideaId] ?? 0;
                $voteCounts[$ideaId] = $count;
                cache()->put("idea_vote_count_{$ideaId}", $count, now()->addMinutes(5));

                // Also update in loadedIdeas array for consistency
                foreach ($this->loadedIdeas as $statusId => &$ideas) {
                    foreach ($ideas as &$idea) {
                        if (($idea['id'] ?? null) == $ideaId) {
                            $idea['votes_count'] = $count;
                            break 2;
                        }
                    }
                }
            }
        }

        return view('livewire.overview.overview-table', [
            'statuses' => $this->statuses,
            'voteUpdateInProgress' => $this->voteUpdateInProgress,
            'voteUpdateMap' => $voteUpdateMap,
            'voteCounts' => $voteCounts,
            'statusTextColors' => $statusTextColors,
            'loadedIdeas' => $this->loadedIdeas,
            'statusTotalCounts' => $this->statusTotalCounts,
            'statusHasMore' => $this->statusHasMore,
            'statusLoading' => $this->statusLoading,
            'recordsPerPage' => self::RECORDS_PER_PAGE,
            'productFilter' => $this->productFilter,
            'categoryFilter' => $this->categoryFilter,
        ]);
    }
}
