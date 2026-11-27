{{-- Kerangka kartu untuk halaman kunci (buat / buka / lupa / ganti). --}}
<div class="mx-auto max-w-md rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    <div class="mb-4 grid size-11 place-items-center rounded-xl bg-teal-50 text-teal-700">
        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
    </div>
    {{ $slot }}
</div>
