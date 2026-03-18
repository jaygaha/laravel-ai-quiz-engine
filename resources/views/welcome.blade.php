<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} — The modern quiz &amp; exam platform</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        {{-- Lexend — optimised for reading ease --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body style="background-color:#F8F9FA; color:#1F2937; font-family:'Lexend',sans-serif; -webkit-font-smoothing:antialiased;">

        {{-- ── Frosted glass navigation ── --}}
        <header style="position:sticky; top:0; z-index:50;" class="glass-bar">
            <div style="max-width:72rem; margin:0 auto; padding:0 1.5rem; display:flex; align-items:center; justify-content:space-between; height:3.75rem;">

                <a href="{{ route('home') }}" style="display:flex; align-items:center; gap:.625rem; text-decoration:none;">
                    <x-app-logo-icon class="size-8" />
                    <span style="font-size:.9375rem; font-weight:700; letter-spacing:-.02em; color:#1F2937;">
                        {{ config('app.name') }}
                    </span>
                </a>

                @if (Route::has('login'))
                    <nav style="display:flex; align-items:center; gap:.75rem;">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-teal" style="padding:.5rem 1.125rem; font-size:.875rem;">
                                Dashboard →
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               style="font-size:.875rem; font-weight:500; color:#4B5563; text-decoration:none; padding:.5rem .75rem; border-radius:8px; transition:color 150ms;"
                               onmouseover="this.style.color='#1F2937'"
                               onmouseout="this.style.color='#4B5563'">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn-teal" style="padding:.5rem 1.125rem; font-size:.875rem;">
                                    Get started free
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        {{-- ── Hero ── --}}
        <section style="padding:6rem 1.5rem 5rem; text-align:center; position:relative; overflow:hidden;">
            {{-- Subtle radial glow — soft coral tint --}}
            <div style="position:absolute; inset:0; pointer-events:none; z-index:0; background:radial-gradient(ellipse 900px 500px at 50% 0%, rgba(255,148,148,.05) 0%, rgba(13,148,136,.04) 60%, transparent 100%);"></div>

            <div style="position:relative; max-width:52rem; margin:0 auto; display:flex; flex-direction:column; align-items:center; gap:1.5rem;">

                {{-- Coral achievement badge --}}
                <div class="badge-coral" style="gap:.5rem;">
                    <span class="dot-coral"></span>
                    Modern exam platform for educators &amp; learners
                </div>

                <h1 style="font-size:clamp(2.5rem,6vw,4rem); font-weight:700; letter-spacing:-.04em; line-height:1.08; color:#1F2937; margin:0;">
                    Create, share &amp; master<br>
                    <span style="color:#0D9488;">exams that matter</span>
                </h1>

                <p style="font-size:1.125rem; color:#6B7280; line-height:1.7; max-width:38rem; margin:0;">
                    QuizForge gives teachers powerful tools to build rich assessments and students
                    an effortless way to practise — with instant grading and detailed feedback.
                </p>

                <div style="display:flex; flex-wrap:wrap; gap:.875rem; justify-content:center; margin-top:.5rem;">
                    @guest
                        <a href="{{ route('register') }}" class="btn-teal" style="font-size:1rem; padding:.875rem 2rem;">
                            Start for free
                        </a>
                        <a href="{{ route('login') }}"
                           style="display:inline-flex; align-items:center; gap:.5rem; background:white; border:1px solid #E5E7EB; color:#1F2937; font-weight:600; font-size:1rem; padding:.875rem 2rem; border-radius:10px; text-decoration:none; transition:border-color 150ms, box-shadow 150ms;"
                           onmouseover="this.style.borderColor='#D1D5DB'; this.style.boxShadow='0 4px 14px -2px rgba(31,41,55,.08)'"
                           onmouseout="this.style.borderColor='#E5E7EB'; this.style.boxShadow='none'">
                            Sign in
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn-teal" style="font-size:1rem; padding:.875rem 2rem;">
                            Go to Dashboard →
                        </a>
                    @endguest
                </div>

                {{-- Stats row --}}
                <div style="display:flex; flex-wrap:wrap; gap:2.5rem; justify-content:center; margin-top:2.5rem; padding-top:2.5rem; border-top:1px solid #E5E7EB; width:100%;">
                    <div style="text-align:center;">
                        <p style="font-size:2rem; font-weight:700; color:#0D9488; letter-spacing:-.04em; margin:0;">3+</p>
                        <p style="font-size:.8125rem; color:#9CA3AF; margin:.25rem 0 0; font-weight:500;">Question types</p>
                    </div>
                    <div style="width:1px; background:#E5E7EB; align-self:stretch;" class="hidden sm:block"></div>
                    <div style="text-align:center;">
                        <p style="font-size:2rem; font-weight:700; color:#0D9488; letter-spacing:-.04em; margin:0;">∞</p>
                        <p style="font-size:.8125rem; color:#9CA3AF; margin:.25rem 0 0; font-weight:500;">Exams &amp; questions</p>
                    </div>
                    <div style="width:1px; background:#E5E7EB; align-self:stretch;" class="hidden sm:block"></div>
                    <div style="text-align:center;">
                        <p style="font-size:2rem; font-weight:700; color:#0D9488; letter-spacing:-.04em; margin:0;">100%</p>
                        <p style="font-size:.8125rem; color:#9CA3AF; margin:.25rem 0 0; font-weight:500;">Instant grading</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ── Bento feature grid ── --}}
        <section style="padding:4rem 1.5rem;">
            <div style="max-width:72rem; margin:0 auto;">

                <div style="text-align:center; margin-bottom:3rem;">
                    <h2 style="font-size:2rem; font-weight:700; letter-spacing:-.03em; margin:0 0 .75rem; color:#1F2937;">
                        Everything in one place
                    </h2>
                    <p style="font-size:1rem; color:#6B7280; margin:0;">
                        A complete toolkit for the modern classroom, from creation to results.
                    </p>
                </div>

                {{-- 3-column bento row — alternating card types to distinguish categories --}}
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1rem;">

                    {{-- Question Types — teal icon --}}
                    <div class="bento-card" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-teal">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Flexible Question Types</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Multiple choice, true/false, and short answer. Mix types freely for any assessment.</p>
                        </div>
                    </div>

                    {{-- Auto-Grading — standard card --}}
                    <div class="bento-card" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-teal">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Instant Auto-Grading</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Scores calculated the moment a student submits. No manual marking required.</p>
                        </div>
                    </div>

                    {{-- Time Limits — soft pink category tint (exam-condition category) --}}
                    <div class="bento-category" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-sunny">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Time-Limited Exams</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Set optional time limits to simulate real exam pressure and build focus skills.</p>
                        </div>
                    </div>

                    {{-- Attempt Tracking — coral icon (achievement context) --}}
                    <div class="bento-category" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-coral">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Attempt Tracking</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Students can resume incomplete attempts and review every past result at any time.</p>
                        </div>
                    </div>

                    {{-- Publish Controls — standard card --}}
                    <div class="bento-card" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-canvas">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Publish Controls</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Keep exams as drafts while building them. Publish with one click when ready.</p>
                        </div>
                    </div>

                    {{-- Role-Based Access — standard card --}}
                    <div class="bento-card" style="display:flex; flex-direction:column; gap:1rem;">
                        <div class="icon-canvas">
                            <svg style="width:20px;height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 style="font-size:1rem; font-weight:600; color:#1F2937; margin:0 0 .375rem; letter-spacing:-.01em;">Role-Based Access</h3>
                            <p style="font-size:.875rem; color:#6B7280; line-height:1.6; margin:0;">Teachers create and manage. Students take and review. Clean separation by role.</p>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- ── Role split bento ── --}}
        <section style="padding:3rem 1.5rem 5rem;">
            <div style="max-width:72rem; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:1rem;">

                {{-- Teacher — Teal card --}}
                <div class="bento-teal" style="display:flex; flex-direction:column; gap:1.5rem;">
                    <div style="display:flex; align-items:center; gap:.875rem;">
                        <div style="width:3rem;height:3rem;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-size:.6875rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.6); margin:0 0 .25rem;">For Teachers</p>
                            <h3 style="font-size:1.25rem; font-weight:700; letter-spacing:-.02em; color:white; margin:0;">Design powerful assessments</h3>
                        </div>
                    </div>

                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.75rem;">
                        @foreach(['Create unlimited exams with no restrictions', 'Add multiple choice, true/false & short answer', 'Set optional time limits per exam', 'Publish drafts when ready, hide anytime'] as $item)
                            <li style="display:flex; align-items:center; gap:.625rem; font-size:.875rem; color:rgba(255,255,255,.9);">
                                <svg style="width:15px;height:15px;flex-shrink:0;color:rgba(255,255,255,.6);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    @guest
                        <a href="{{ route('register') }}"
                           style="display:inline-flex;align-items:center;gap:.5rem;background:white;color:#0D9488;font-weight:700;font-size:.875rem;padding:.625rem 1.25rem;border-radius:10px;text-decoration:none;width:fit-content;transition:opacity 150ms;"
                           onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                            Register as teacher →
                        </a>
                    @endguest
                </div>

                {{-- Student — white card with soft pink category tint --}}
                <div class="bento-category" style="display:flex; flex-direction:column; gap:1.5rem;">
                    <div style="display:flex; align-items:center; gap:.875rem;">
                        <div class="icon-teal" style="width:3rem;height:3rem;border-radius:12px;">
                            <svg style="width:22px;height:22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                            </svg>
                        </div>
                        <div>
                            <p style="font-size:.6875rem; font-weight:600; letter-spacing:.08em; text-transform:uppercase; color:#0D9488; margin:0 0 .25rem;">For Students</p>
                            <h3 style="font-size:1.25rem; font-weight:700; letter-spacing:-.02em; color:#1F2937; margin:0;">Master any subject</h3>
                        </div>
                    </div>

                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.75rem;">
                        @foreach(['Browse all published exams instantly', 'Resume unfinished attempts anytime', 'Get your score immediately on submit', 'Review every question with correct answers'] as $item)
                            <li style="display:flex; align-items:center; gap:.625rem; font-size:.875rem; color:#4B5563;">
                                <svg style="width:15px;height:15px;flex-shrink:0;color:#0D9488;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- Progress bar decoration — coral accent on percentage --}}
                    <div style="padding-top:.5rem; display:flex; flex-direction:column; gap:.5rem;">
                        <div style="display:flex; justify-content:space-between; font-size:.75rem; font-weight:600; color:#9CA3AF;">
                            <span>Sample progress</span>
                            <span style="color:#FF6B6B;">75%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:75%;"></div>
                        </div>
                    </div>

                    @guest
                        <a href="{{ route('register') }}"
                           style="display:inline-flex;align-items:center;gap:.5rem;background:#F0FDFA;color:#0F766E;border:1px solid #99F6E4;font-weight:700;font-size:.875rem;padding:.625rem 1.25rem;border-radius:10px;text-decoration:none;width:fit-content;transition:background-color 150ms;"
                           onmouseover="this.style.backgroundColor='#CCFBF1'" onmouseout="this.style.backgroundColor='#F0FDFA'">
                            Register as student →
                        </a>
                    @endguest
                </div>

            </div>
        </section>

        {{-- ── CTA ── --}}
        <section style="padding:5rem 1.5rem; text-align:center;">
            <div style="max-width:38rem; margin:0 auto; display:flex; flex-direction:column; align-items:center; gap:1.25rem;">
                <h2 style="font-size:2.25rem; font-weight:700; letter-spacing:-.04em; color:#1F2937; margin:0;">
                    Ready to get started?
                </h2>
                <p style="font-size:1rem; color:#6B7280; margin:0; line-height:1.7;">
                    Join educators and learners already using QuizForge to create and ace exams.
                </p>
                @guest
                    <a href="{{ route('register') }}" class="btn-teal" style="font-size:1rem; padding:1rem 2.5rem; margin-top:.5rem;">
                        Create your free account
                    </a>
                @endguest
            </div>
        </section>

        {{-- ── Footer ── --}}
        <footer style="border-top:1px solid #E5E7EB; padding:1.75rem 1.5rem;">
            <div style="max-width:72rem; margin:0 auto; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;">
                <div style="display:flex; align-items:center; gap:.625rem;">
                    <x-app-logo-icon class="size-6" />
                    <span style="font-size:.875rem; font-weight:600; color:#4B5563;">{{ config('app.name') }}</span>
                </div>
                <p style="font-size:.75rem; color:#9CA3AF; margin:0;">
                    © {{ date('Y') }} {{ config('app.name') }}. Built with Laravel &amp; Livewire.
                </p>
            </div>
        </footer>

    </body>
</html>
