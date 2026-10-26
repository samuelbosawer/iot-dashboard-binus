<x-layouts.app title="Prediksi ML">
    <div class="mb-6">
        <p class="text-xs font-semibold tracking-widest text-brand-600 uppercase">Machine Learning · Dalam Pengembangan</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-900">Prediksi Sensor Soil &amp; Water</h1>
        <p class="text-sm text-slate-500">Rencana model prediksi kelembaban tanah, air tandon (float switch), dan suhu tanah, beserta kesiapan dataset untuk pelatihan.</p>
    </div>

    <x-prediction.overview :dataset="$dataset" :models="$models" :hourly="$hourly" :trend="$trend" :recommendations="$recommendations" :soil-dry-percent="$soilDryPercent" :download-url="route('dashboard').'#export-form'" />
</x-layouts.app>
