<nav class="flex flex-wrap items-center gap-1 text-sm">
    @foreach (['laporan.summary' => 'Ringkasan', 'laporan.people' => 'Per karyawan', 'laporan.change-key' => 'Ganti kunci'] as $route => $label)
        <a href="{{ route($route) }}"
           class="rounded-lg px-3 py-1.5 {{ request()->routeIs($route) ? 'bg-teal-50 font-semibold text-teal-800' : 'text-slate-600 hover:bg-stone-100' }}">{{ $label }}</a>
    @endforeach
    <button type="button" onclick="window.print()" class="rounded-lg px-3 py-1.5 text-slate-600 hover:bg-stone-100">Cetak</button>
    <form method="POST" action="{{ route('laporan.lock') }}">
        @csrf
        <button class="rounded-lg bg-slate-800 px-3 py-1.5 font-semibold text-white hover:bg-slate-900">Kunci</button>
    </form>
</nav>
