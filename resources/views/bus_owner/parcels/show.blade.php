@extends('admin.app')
@section('title', $parcel->parcel_number)
@section('content')
<div class="container mx-auto px-4 py-6">
    @include('parcels.partials.process')
</div>
@endsection
