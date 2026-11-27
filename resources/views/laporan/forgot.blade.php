@extends('layouts.app')
@section('title', 'Lupa kunci')
@section('home', route('laporan.index'))

@section('content')
<x-key-card>
    <h1 class="text-xl font-bold text-slate-900">Lupa kunci</h1>
    <p class="mt-2 text-sm text-slate-600">Masukkan <strong>kode pemulihan</strong> yang dicatat saat kunci pertama kali dibuat, lalu buat kunci baru. Semua data tetap aman dan tidak hilang.</p>
    <form method="POST" action="{{ route('laporan.forgot.submit') }}">
        @csrf
        <label class="mt-4 block">
            <span class="text-sm font-medium text-slate-700">Kode pemulihan</span>
            <input name="recovery" value="{{ old('recovery') }}" required autofocus autocomplete="off" placeholder="XXXX-XXXX-XXXX-XXXX"
                   class="mt-1 w-full rounded-lg border px-3 py-2 font-mono uppercase tracking-wider focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none {{ $errors->has('recovery') ? 'border-red-400' : 'border-stone-300' }}">
            @error('recovery') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
        </label>
        <x-key-input label="Kunci baru" hint="Minimal 6 karakter." />
        <x-key-input name="key_confirmation" label="Ulangi kunci baru" />
        <button class="mt-6 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Simpan kunci baru</button>
    </form>
    <a href="{{ route('laporan.index') }}" class="mt-4 block text-center text-sm text-slate-500 hover:underline">Kembali</a>
</x-key-card>
@endsection
