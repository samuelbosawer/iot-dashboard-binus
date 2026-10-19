{{-- App-style bottom tab bar for phones; sections are highlighted on scroll by resources/js/app.js --}}
@php
    $home = request()->routeIs('visitor') ? '' : route('visitor');
    $tabs = [
        ['href' => $home.'#live', 'section' => 'live', 'label' => 'Live', 'icon' => 'fa-house-signal'],
        ['href' => $home.'#laporan', 'section' => 'laporan', 'label' => 'Laporan', 'icon' => 'fa-chart-pie'],
        ['href' => $home.'#grafik', 'section' => 'grafik', 'label' => 'Grafik', 'icon' => 'fa-chart-line'],
        ['href' => $home.'#tabel', 'section' => 'tabel', 'label' => 'Data', 'icon' => 'fa-table-list'],
        ['href' => route('prediction'), 'section' => null, 'label' => 'Prediksi', 'icon' => 'fa-brain', 'active' => request()->routeIs('prediction')],
    ];
@endphp

<div class="h-[calc(4.5rem+env(safe-area-inset-bottom))] md:hidden" aria-hidden="true"></div>

<nav data-bottom-nav aria-label="Menu utama" class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-4px_20px_rgb(15_23_42/0.06)] backdrop-blur md:hidden">
    <ul class="mx-auto grid max-w-md grid-cols-5">
        @foreach ($tabs as $tab)
            <li>
                <a href="{{ $tab['href'] }}"
                    @if ($tab['section']) data-section="{{ $tab['section'] }}" @endif
                    @if ($tab['active'] ?? false) data-active aria-current="page" @endif
                    class="group flex flex-col items-center gap-1 px-1 pt-2 pb-2.5 text-[11px] font-medium text-slate-500 transition [-webkit-tap-highlight-color:transparent] data-active:font-semibold data-active:text-brand-700">
                    <span class="flex h-8 w-14 items-center justify-center rounded-full transition group-active:scale-90 group-data-active:bg-brand-100">
                        <i class="fa-solid {{ $tab['icon'] }} text-base"></i>
                    </span>
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
