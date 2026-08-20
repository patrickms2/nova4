@props([
    'team' => null,
    'component' => 'button',
])

@if ($team)
    <form method="POST" action="{{ route('current-team.update') }}">
        @method('PUT')
        @csrf

        <input type="hidden" name="team_id" value="{{ $team->id }}" />

        @if ($component === 'button')
            <x-dropdown-link href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                <div class="flex items-center">
                    @if (Auth::user()->isCurrentTeam($team))
                        <svg class="me-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @endif

                    <div class="truncate">{{ $team->name }}</div>
                </div>
            </x-dropdown-link>
        @else
            <x-responsive-nav-link href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                <div class="flex items-center">
                    @if (Auth::user()->isCurrentTeam($team))
                        <svg class="me-2 h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @endif

                    <div class="truncate">{{ $team->name }}</div>
                </div>
            </x-responsive-nav-link>
        @endif
    </form>
@endif
