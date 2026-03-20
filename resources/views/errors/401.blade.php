@extends('errors.layout', ['color' => '#F59E0B'])

@section('code', '401')
@section('title', 'Authentication Required')
@section('message', 'You need to sign in before you can access this page. Please log in and try again.')

@section('extra-action')
    <a href="{{ route('login') }}" style="font-size:0.875rem; font-weight:500; color:#0D9488; text-decoration:none;"
       onmouseover="this.style.textDecoration='underline';"
       onmouseout="this.style.textDecoration='none';">
        Sign in
    </a>
@endsection
