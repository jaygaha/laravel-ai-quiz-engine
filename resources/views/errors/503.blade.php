@extends('errors.layout', ['color' => '#F59E0B'])

@section('code', '503')
@section('title', 'Under Maintenance')
@section('message', 'We\'re currently performing scheduled maintenance. We\'ll be back shortly — thanks for your patience!')

@section('extra-action')
    <a href="javascript:location.reload();" style="font-size:0.875rem; font-weight:500; color:#0D9488; text-decoration:none;"
       onmouseover="this.style.textDecoration='underline';"
       onmouseout="this.style.textDecoration='none';">
        Try again
    </a>
@endsection
