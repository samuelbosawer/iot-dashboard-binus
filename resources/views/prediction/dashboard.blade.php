<x-layouts.app title="Prediksi ML">
    <div class="relative mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white px-5 py-6 shadow-sm">
        <x-prediction.watermark variant="dark" />

        <div class="relative">
            <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Machine Learning · Dalam Pengembangan</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Prediksi Sensor Soil &amp; Water</h1>
            <p class="text-sm text-slate-500">Rencana model prediksi kelembaban tanah, air tandon (float switch), dan suhu tanah, beserta kesiapan dataset untuk pelatihan.</p>
        </div>
    </div>

    <x-prediction.overview :dataset="$dataset" :models="$models" :hourly="$hourly" :trend="$trend" :recommendations="$recommendations" :soil-dry-percent="$soilDryPercent" :download-url="route('dashboard').'#export-form'" />
</x-layouts.app>
