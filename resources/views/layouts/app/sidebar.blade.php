<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased" style="background-color:#F8F9FA; color:#1F2937; font-family:'Lexend',sans-serif;">

        {{-- ── Sidebar ──────────────────────────────────── --}}
        <flux:sidebar sticky collapsible="mobile"
            style="background-color:#FFFFFF; border-right:1px solid #E5E7EB;"
            class="shadow-sm">

            {{-- Brand --}}
            <flux:sidebar.header style="border-bottom:1px solid #F3F4F6; padding:1rem 1rem 0.875rem;">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5 group">
                    <x-app-logo-icon class="size-8 shrink-0" />
                    <span style="font-size:1rem; font-weight:700; letter-spacing:-0.02em; color:#1F2937;">
                        {{ config('app.name') }}
                    </span>
                </a>
                <flux:sidebar.collapse class="lg:hidden" style="color:#9CA3AF;" />
            </flux:sidebar.header>

            {{-- Navigation --}}
            <flux:sidebar.nav style="padding:0.75rem 0.5rem;">
                @auth
                    @if (auth()->user()->isTeacher())
                        <div style="padding:0 0.5rem 0.375rem; font-size:0.6875rem; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:#9CA3AF;">
                            Teaching
                        </div>
                        <flux:sidebar.item
                            icon="academic-cap"
                            :href="route('teacher.exams.index')"
                            :current="request()->routeIs('teacher.exams.*')"
                            wire:navigate
                        >
                            My Exams
                        </flux:sidebar.item>
                        <flux:sidebar.item
                            icon="rectangle-stack"
                            :href="route('teacher.questions.index')"
                            :current="request()->routeIs('teacher.questions.*')"
                            wire:navigate
                        >
                            Question Bank
                        </flux:sidebar.item>
                    @else
                        <div style="padding:0 0.5rem 0.375rem; font-size:0.6875rem; font-weight:600; letter-spacing:.07em; text-transform:uppercase; color:#9CA3AF;">
                            Learning
                        </div>
                        <flux:sidebar.item
                            icon="book-open"
                            :href="route('student.dashboard')"
                            :current="request()->routeIs('student.*')"
                            wire:navigate
                        >
                            Available Exams
                        </flux:sidebar.item>
                    @endif
                @endauth
            </flux:sidebar.nav>

            <flux:spacer />

            {{-- Role badge --}}
            @auth
                <div style="padding:0 0.75rem 0.75rem;">
                    <div style="display:flex;align-items:center;gap:0.5rem;background:#F0FDFA;border:1px solid #99F6E4;border-radius:10px;padding:0.5rem 0.75rem;">
                        <svg style="width:14px;height:14px;color:#0D9488;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span style="font-size:0.75rem;font-weight:600;color:#0F766E;text-transform:capitalize;">
                            {{ auth()->user()->role->value }}
                        </span>
                    </div>
                </div>
            @endauth

            {{-- User menu (desktop) --}}
            <div style="border-top:1px solid #F3F4F6; padding:0.5rem;">
                <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
            </div>
        </flux:sidebar>

        {{-- ── Mobile header ────────────────────────────── --}}
        <flux:header class="lg:hidden glass-bar">
            <flux:sidebar.toggle icon="bars-2" inset="left" style="color:#4B5563;" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                            <div class="grid flex-1 leading-tight">
                                <span class="truncate font-medium text-sm" style="color:#1F2937;">{{ auth()->user()->name }}</span>
                                <span class="truncate text-xs" style="color:#9CA3AF;">{{ auth()->user()->email }}</span>
                            </div>
                        </div>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>Settings</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">
                            Log out
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- ── Page slot ────────────────────────────────── --}}
        <flux:main style="padding:2rem 2rem 3rem;">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
