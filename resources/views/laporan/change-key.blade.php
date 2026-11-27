@extends('layouts.app')
@section('title', 'Ganti kunci')
@section('home', route('laporan.summary'))
@section('nav') @include('laporan._nav') @endsection

@section('content')
<x-key-card>
    <h1 class="text-xl font-bold text-slate-900">Ganti kunci</h1>
    <p class="mt-2 text-sm text-slate-600">Kode pemulihan tetap sama.</p>
    <form method="POST" action="{{ route('laporan.change-key.submit') }}">
        @csrf
        <x-key-input label="Kunci baru" :autofocus="true" hint="Minimal 6 karakter." />
        <x-key-input name="key_confirmation" label="Ulangi kunci baru" />
        <button class="mt-6 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Simpan</button>
    </form>
</x-key-card>
@endsection
