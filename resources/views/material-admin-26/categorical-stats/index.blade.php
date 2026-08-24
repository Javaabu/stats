@extends(config('stats.default_layout'))

@section('title', __('Categorical Stats'))
@section('page-title', __('Categorical Stats'))

@section('content')
    <x-stats::categorical />
@endsection
