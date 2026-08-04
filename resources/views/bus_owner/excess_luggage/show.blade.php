@extends('admin.app')

@section('title', __('vender/luggage.process_title'))

@section('content')
<div class="container mx-auto px-4 py-6">
    @include('excess_luggage.partials.process')
</div>
@endsection
