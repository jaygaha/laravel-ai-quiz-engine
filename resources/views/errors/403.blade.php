@extends('errors.layout', ['color' => '#FF9494'])

@section('code', '403')
@section('title', 'Access Denied')
@section('message', $exception->getMessage() ?: 'You don\'t have permission to access this page. If you think this is a mistake, contact your teacher or administrator.')
