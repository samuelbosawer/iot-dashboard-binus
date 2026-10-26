<x-layouts.guest title="Prediksi Machine Learning" description="Grafik data sensor asli, tren kelembaban tanah, rencana prediksi kelembaban tanah, air tandon, dan suhu tanah, serta rekomendasi perawatan tanaman.">
    <x-visitor.navbar />

    <section class="relative overflow-hidden bg-linear-to-br from-brand-600 via-brand-800 to-brand-900 text-white">
        <x-prediction.watermark />

        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-widest text-brand-100 uppercase ring-1 ring-white/20">
                <i class="fa-solid fa-brain"></i>
                Machine Learning · Dalam Pengembangan
            </span>
            <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-4xl">Prediksi Sensor Soil &amp; Water</h1>
            <p class="mt-3 max-w-2xl text-brand-100">Data sensor yang terkumpul akan dipakai untuk melatih model yang memperkirakan kelembaban tanah, kapan air tandon habis, dan suhu tanah.</p>
        </div>
    </section>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">
        <x-prediction.overview :dataset="$dataset" :models="$models" :hourly="$hourly" :trend="$trend" :recommendations="$recommendations" :soil-dry-percent="$soilDryPercent" :download-url="route('visitor').'#download'" />
    </main>

    <x-visitor.bottom-nav />
</x-layouts.guest>
