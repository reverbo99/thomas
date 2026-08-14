@extends('vender.app')
@section('title', $parcel->parcel_number)
@section('content')
<div class="vendor-dash fade-in px-4 py-6">
    @include('parcels.partials.process', ['test_mode' => $test_mode ?? \App\Models\Setting::isTestMode()])
</div>
@endsection
