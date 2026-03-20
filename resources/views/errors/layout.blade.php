<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased" style="background-color:#F8F9FA; font-family:'Lexend',sans-serif;">

        <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; padding:2rem 1.5rem; text-align:center;">

            {{-- Logo --}}
            <a href="{{ url('/') }}" class="mb-10 flex items-center gap-2">
                <x-app-logo-icon class="size-9" />
                <span style="font-size:1.125rem; font-weight:700; color:#1F2937;">{{ config('app.name') }}</span>
            </a>

            {{-- Error card --}}
            <div style="width:100%; max-width:28rem; background:#FFFFFF; border:1px solid #E5E7EB; border-radius:14px; padding:2.5rem 2rem; box-shadow:0 1px 3px 0 rgba(31,41,55,.06), 0 1px 2px -1px rgba(31,41,55,.04);">

                {{-- Status code --}}
                <div style="font-size:4.5rem; font-weight:700; letter-spacing:-0.04em; line-height:1; color:{{ $color ?? '#0D9488' }}; margin-bottom:0.75rem;">
                    @yield('code')
                </div>

                {{-- Title --}}
                <h1 style="font-size:1.25rem; font-weight:700; color:#1F2937; margin-bottom:0.5rem; letter-spacing:-0.025em;">
                    @yield('title')
                </h1>

                {{-- Message --}}
                <p style="font-size:0.9375rem; color:#6B7280; line-height:1.6; margin-bottom:1.75rem;">
                    @yield('message')
                </p>

                {{-- Action --}}
                <div style="display:flex; flex-direction:column; gap:0.75rem; align-items:center;">
                    <a href="{{ url('/') }}" style="display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; background-color:#0D9488; color:white; font-weight:600; font-size:0.875rem; padding:0.625rem 1.5rem; border-radius:10px; text-decoration:none; transition:background-color 150ms ease, box-shadow 150ms ease;"
                       onmouseover="this.style.backgroundColor='#0F766E'; this.style.boxShadow='0 4px 14px -2px rgba(13,148,136,.4)';"
                       onmouseout="this.style.backgroundColor='#0D9488'; this.style.boxShadow='none';">
                        <svg style="width:16px; height:16px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        Go Home
                    </a>

                    @hasSection('extra-action')
                        @yield('extra-action')
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <p style="margin-top:2rem; font-size:0.75rem; color:#9CA3AF;">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>

        @fluxScripts
    </body>
</html>
