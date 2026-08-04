@extends('vender.app')

@section('title', __('vender/luggage.process_title'))

@section('content')
<div class="vendor-dash fade-in px-4 py-6">
    @include('excess_luggage.partials.process')
</div>
@endsection
