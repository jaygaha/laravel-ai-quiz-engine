@extends('errors.layout', ['color' => '#F59E0B'])

@section('code', '419')
@section('title', 'Session Expired')
@section('message', 'Your session has expired. Please refresh the page and try again.')

@section('extra-action')
    <a href="javascript:location.reload();" style="font-size:0.875rem; font-weight:500; color:#0D9488; text-decoration:none;"
       onmouseover="this.style.textDecoration='underline';"
       onmouseout="this.style.textDecoration='none';">
        Refresh page
    </a>
@endsection
