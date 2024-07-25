@extends('admin.stats.stats')

@section('page-title', __('Statistics'))

@section('content')
    @include('admin.stats._generator', ['url' => route('admin.stats.general-export')])
@endsection
