@extends('layouts.app')
@section('title', 'Mulai')

@section('content')
<div class="mx-auto max-w-xl">
    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">Survei Kesehatan Mental Karyawan</h1>
    <p class="mt-3 text-slate-600">
        Survei ini dibuat atas permintaan langsung Pak Andy untuk memahami kondisi dan kesejahteraan karyawan.
        Pengisian sekitar 2–3 menit per hari, selama 6 hari.
    </p>

    <div class="mt-5 flex gap-3 rounded-xl border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900">
        <svg viewBox="0 0 24 24" class="mt-0.5 size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <p>Jawaban Anda dikunci (dienkripsi) dan <strong>hanya bisa dibuka oleh Pak Andy</strong>. Management, atasan, maupun panitia tidak dapat membacanya. Nama boleh dikosongkan.</p>
    </div>

    @unless ($anyOpen)
        <div class="mt-6 rounded-xl border border-stone-200 bg-white p-4 text-sm text-slate-600">Survei belum dibuka. Silakan kembali saat panitia mengumumkan survei dimulai.</div>
    @endunless

    @error('code')
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">{{ $message }}</div>
    @enderror

    <div class="mt-6 grid gap-4">
        <form method="POST" action="{{ route('survei.start') }}" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="mode" value="lanjut">
            <h2 class="font-semibold text-slate-900">Sudah punya kode?</h2>
            <p class="mt-1 text-sm text-slate-500">Masukkan kode yang Anda dapat setelah mengisi Hari 1.</p>
            <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                <input name="code" value="{{ old('code') }}" placeholder="Contoh: K7PQ3M" autocomplete="off" autocapitalize="characters"
                       class="min-w-0 flex-1 rounded-lg border border-stone-300 px-4 py-2.5 font-mono text-lg uppercase tracking-widest placeholder:font-sans placeholder:text-base placeholder:normal-case placeholder:tracking-normal focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none">
                <button class="rounded-lg bg-teal-700 px-5 py-2.5 font-semibold text-white hover:bg-teal-800">Lanjutkan</button>
            </div>
        </form>

        <form method="POST" action="{{ route('survei.start') }}" class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            @csrf
            <input type="hidden" name="mode" value="baru">
            <h2 class="font-semibold text-slate-900">Belum punya kode atau lupa kode?</h2>
            <p class="mt-1 text-sm text-slate-500">Mulai dari Hari 1. Setelah selesai, Anda akan mendapat kode untuk mengisi hari-hari berikutnya.</p>
            <button class="mt-4 rounded-lg border border-teal-700 px-5 py-2.5 font-semibold text-teal-800 hover:bg-teal-50">Mulai dari Hari 1</button>
        </form>
    </div>
</div>
@endsection
