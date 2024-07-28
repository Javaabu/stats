@extends('stats::material-admin-26.time-series-stats.stats')

@section('page-title', __('Time Series Statistics'))

@section('content')
    @include('stats::material-admin-26.time-series-stats._generator', ['url' => action([\Javaabu\Stats\Http\Controllers\TimeSeriesStatsController::class, 'export'])])
@endsection
