@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1.5 space-y-1 text-sm text-red-600']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-center gap-1.5">
                <i class="fa-solid fa-circle-exclamation text-xs"></i>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
