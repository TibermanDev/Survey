@extends('layouts.app')
@section('title', 'Panitia')

@section('content')
<form method="POST" action="{{ route('panitia.login') }}" class="mx-auto max-w-sm rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
    @csrf
    <h1 class="text-xl font-bold text-slate-900">Masuk panitia</h1>
    <p class="mt-1 text-sm text-slate-500">Untuk membuka dan menutup hari survei. Panitia tidak bisa melihat jawaban karyawan.</p>
    <label class="mt-5 block">
        <span class="text-sm font-medium text-slate-700">Password panitia</span>
        <input type="password" name="password" autofocus required
               class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none">
    </label>
    @error('password') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    <button class="mt-5 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white hover:bg-teal-800">Masuk</button>
</form>
@endsection
