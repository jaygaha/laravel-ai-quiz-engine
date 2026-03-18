<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased" style="background-color:#F8F9FA; font-family:'Lexend',sans-serif;">

        <div style="display:flex; min-height:100vh;">

            {{-- ── Left: Teal brand panel (desktop only) ── --}}
            <div class="hidden lg:flex" style="width:420px; flex-shrink:0; flex-direction:column; justify-content:space-between; background:linear-gradient(160deg, #0D9488 0%, #0F766E 100%); padding:3rem; color:white;">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <x-app-logo-icon class="size-10" />
                    <span style="font-size:1.25rem; font-weight:700; letter-spacing:-0.02em;">
                        {{ config('app.name') }}
                    </span>
                </a>

                {{-- Callout --}}
                <div style="display:flex; flex-direction:column; gap:2rem;">
                    <blockquote style="display:flex; flex-direction:column; gap:0.75rem;">
                        <p style="font-size:1.5rem; font-weight:700; line-height:1.35; letter-spacing:-0.02em; color:white;">
                            "Testing yourself is the single most effective study strategy known to science."
                        </p>
                        <footer style="font-size:0.8125rem; color:rgba(255,255,255,.6);">
                            — Roediger & Butler, 2011
                        </footer>
                    </blockquote>

                    <ul style="display:flex; flex-direction:column; gap:1rem; list-style:none; padding:0; margin:0;">
                        <li style="display:flex; align-items:flex-start; gap:0.875rem;">
                            <div style="margin-top:2px; width:2rem; height:2rem; background:rgba(255,255,255,.15); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                            <div>
                                <p style="font-size:.875rem; font-weight:600; margin-bottom:.125rem;">Multiple question types</p>
                                <p style="font-size:.8125rem; color:rgba(255,255,255,.65); line-height:1.5;">Multiple choice, true/false, and short answer.</p>
                            </div>
                        </li>
                        <li style="display:flex; align-items:flex-start; gap:0.875rem;">
                            <div style="margin-top:2px; width:2rem; height:2rem; background:rgba(255,255,255,.15); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p style="font-size:.875rem; font-weight:600; margin-bottom:.125rem;">Instant auto-grading</p>
                                <p style="font-size:.8125rem; color:rgba(255,255,255,.65); line-height:1.5;">Scores and per-question feedback immediately.</p>
                            </div>
                        </li>
                        <li style="display:flex; align-items:flex-start; gap:0.875rem;">
                            <div style="margin-top:2px; width:2rem; height:2rem; background:rgba(255,255,255,.15); border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <p style="font-size:.875rem; font-weight:600; margin-bottom:.125rem;">Optional time limits</p>
                                <p style="font-size:.8125rem; color:rgba(255,255,255,.65); line-height:1.5;">Simulate real exam conditions for any subject.</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <p style="font-size:.75rem; color:rgba(255,255,255,.45);">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>

            {{-- ── Right: form panel ── --}}
            <div style="flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem 1.5rem;">
                {{-- Mobile logo --}}
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 lg:hidden" wire:navigate>
                    <x-app-logo-icon class="size-8" />
                    <span style="font-size:1.125rem; font-weight:700; color:#1F2937;">{{ config('app.name') }}</span>
                </a>

                <div style="width:100%; max-width:22rem;">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
