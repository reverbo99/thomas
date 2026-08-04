@if (session('success'))
    <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif
