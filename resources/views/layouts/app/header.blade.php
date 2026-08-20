<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
    <head>
        @include('partials.head')
    </head>
<body class="relative min-h-screen text-white overflow-x-hidden">


<div class="min-h-screen flex">

  {{-- Sidebar --}}
  <aside class="w-64 p-4">
    <div class="nova-glass-2 rounded-3xl p-4 h-full flex flex-col gap-2">

      <div class="text-xs uppercase tracking-widest text-white/40 mb-4">
        Nova
      </div>

      <a href="#" class="sidebar-item active">Dashboard</a>
      <a href="#" class="sidebar-item">Requests</a>
      <a href="#" class="sidebar-item">Projects</a>
      <a href="#" class="sidebar-item">Settings</a>

    </div>
  </aside>

  {{-- Content --}}
  <main class="flex-1 p-10">
    <div class="max-w-4xl mx-auto">
      {{-- aquí tu NovaHome --}}
    </div>
  </main>

</div>
  
<flux:header
  container
  class="border-b border-white/5 bg-white/[0.03] backdrop-blur-xl
         shadow-[0_20px_60px_rgba(0,0,0,0.35)]"
         >
            
            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5"
                        icon="book-open-text"
                        href="https://laravel.com/docs/starter-kits#livewire"
                        target="_blank"
                        :label="__('Documentation')"
                    />
                </flux:tooltip>
            </flux:navbar>

            <x-desktop-user-menu />
        </flux:header>

        <!-- Mobile Menu -->
<flux:sidebar
  collapsible="mobile"
  sticky
  class="lg:hidden border-e border-white/5 bg-white/[0.025] backdrop-blur-xl">
              <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
<flux:sidebar.item
  icon="layout-grid"
  :href="route('dashboard')"
  :current="request()->routeIs('dashboard')"
  wire:navigate
  class="rounded-xl px-3 py-2 text-white/80
         hover:bg-white/[0.06] hover:text-white
         data-[active=true]:bg-white/[0.08] data-[active=true]:text-white
         ring-1 ring-transparent hover:ring-white/10 data-[active=true]:ring-white/10
         transition-all"
>
  {{ __('Dashboard') }}
</flux:sidebar.item>
            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts

    </body>
</html>
