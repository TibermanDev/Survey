@extends('layouts.app')
@section('title', 'Terima kasih')

@section('content')
<div class="mx-auto max-w-xl text-center">
    <div class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-100 text-emerald-700">
        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    </div>
    <h1 class="mt-4 text-2xl font-bold text-slate-900">Jawaban Hari {{ $dayNumber }} tersimpan</h1>
    <p class="mt-2 text-slate-600">Terima kasih sudah meluangkan waktu.</p>

    @if ($finished)
        <p class="mt-6 rounded-xl border border-teal-200 bg-teal-50 p-4 text-teal-900">Anda sudah menyelesaikan seluruh survei (Hari 1–6). Terima kasih banyak atas kejujuran Anda.</p>
    @else
        <div class="mt-8 rounded-2xl border-2 border-dashed border-teal-600 bg-white p-6">
            <p class="text-sm font-medium text-slate-600">Kode pribadi Anda</p>
            <p id="kode" class="mt-2 font-mono text-4xl font-bold tracking-[0.3em] text-teal-800 select-all">{{ $code }}</p>
            <button type="button" onclick="navigator.clipboard.writeText('{{ $code }}').then(() => this.textContent = 'Kode tersalin')"
                    class="mt-4 rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium hover:bg-stone-50">Salin kode</button>
        </div>
        <p class="mt-4 text-sm text-slate-600">
            <strong>Simpan kode ini</strong> (tulis, screenshot, atau salin ke catatan HP). Kode ini diminta saat mengisi hari berikutnya.
            Jika lupa, Anda harus mengulang dari Hari 1.
        </p>
    @endif

    @if ($showHelp)
        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-5 text-left text-sm text-rose-900">
            {{ config('survei.help_text') }}
        </div>
    @endif

    @if ($nextDay)
        <form method="POST" action="{{ route('survei.start') }}" class="mt-8">
            @csrf
            <input type="hidden" name="mode" value="lanjut">
            <input type="hidden" name="code" value="{{ $code }}">
            <button class="rounded-lg bg-teal-700 px-6 py-3 font-semibold text-white hover:bg-teal-800">Lanjut isi Hari {{ $nextDay }}</button>
        </form>
    @endif
</div>
@endsection
