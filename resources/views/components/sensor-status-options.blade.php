{{-- Options of a "Status" <select>, grouped like App\Enums\SensorStatusFilter --}}
@props(['selected' => null])

<option value="">Semua status</option>
@foreach (\App\Enums\SensorStatusFilter::grouped() as $group => $statuses)
    <optgroup label="{{ $group }}">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected($selected === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </optgroup>
@endforeach
