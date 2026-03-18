<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased" style="background-color:#F8F9FA; font-family:'Lexend',sans-serif;">
        <div style="min-height:100vh; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:2rem;">
            <div style="width:100%; max-width:26rem; display:flex; flex-direction:column; gap:1.5rem;">
                <a href="{{ route('home') }}" style="display:flex; flex-direction:column; align-items:center; gap:0.5rem; text-decoration:none;" wire:navigate>
                    <x-app-logo-icon class="size-10" />
                    <span class="sr-only">{{ config('app.name') }}</span>
                </a>
                <div class="bento-flat" style="padding:2.5rem 2rem;">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
