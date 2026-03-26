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
                        <flux:sidebar.item
                            icon="chart-bar-square"
                            :href="route('teacher.ai-usage')"
                            :current="request()->routeIs('teacher.ai-usage')"
                            wire:navigate
                        >
                            AI Usage
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

        {{-- Toast notifications (teleported to body to escape transform contexts) --}}
        @teleport('body')
        @persist('toast')
            <div
                x-data="{ toasts: [], remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) } }"
                x-on:toast.window="
                    let t = { id: Date.now(), ...$event.detail };
                    toasts.push(t);
                    setTimeout(() => remove(t.id), t.duration || 4000)
                "
                style="position:fixed; bottom:1rem; right:1rem; z-index:9999; display:flex; flex-direction:column-reverse; gap:0.5rem; width:20rem;"
            >
                <template x-for="toast in toasts" :key="toast.id">
                    <div
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-[-0.5rem]"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="rounded-lg border px-4 py-3 shadow-lg text-sm flex items-start gap-3"
                        :class="{
                            'bg-emerald-50 border-emerald-200 text-emerald-800': toast.variant === 'success',
                            'bg-amber-50 border-amber-200 text-amber-800': toast.variant === 'warning',
                            'bg-red-50 border-red-200 text-red-800': toast.variant === 'danger',
                            'bg-white border-zinc-200 text-zinc-800': !toast.variant || toast.variant === 'info',
                        }"
                    >
                        <template x-if="toast.variant === 'success'">
                            <svg class="size-5 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="toast.variant === 'warning'">
                            <svg class="size-5 shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                        </template>
                        <template x-if="toast.variant === 'danger'">
                            <svg class="size-5 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                        </template>
                        <div class="flex-1">
                            <p x-show="toast.heading" x-text="toast.heading" class="font-semibold"></p>
                            <p x-text="toast.text"></p>
                        </div>
                        <button x-on:click="remove(toast.id)" class="shrink-0 opacity-50 hover:opacity-100">
                            <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/></svg>
                        </button>
                    </div>
                </template>
            </div>
        @endpersist
        @endteleport

        @fluxScripts
    </body>
</html>
