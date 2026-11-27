@extends('layouts.app')
@section('title', "Hari $dayNumber")

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="flex items-center justify-between gap-4 text-sm text-slate-500">
        <span>Hari {{ $dayNumber }} dari 6</span>
        @if ($code)
            <span>Kode: <span class="font-mono font-semibold text-slate-700">{{ $code }}</span></span>
        @endif
    </div>
    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-stone-200">
        <div class="h-full rounded-full bg-teal-600" style="width: {{ round(($dayNumber - 1) / 6 * 100) }}%"></div>
    </div>

    <h1 class="mt-6 text-2xl font-bold text-slate-900">Hari {{ $dayNumber }}: {{ $day['title'] }}</h1>
    @isset($day['intro'])
        <p class="mt-2 text-slate-600">{{ $day['intro'] }}</p>
    @endisset

    @if ($errors->any())
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            Masih ada pertanyaan yang belum dijawab. Silakan periksa bagian yang ditandai merah.
        </div>
    @endif

    <form method="POST" action="{{ route('survei.submit') }}" class="mt-6 space-y-4">
        @csrf

        @if ($isNew)
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Data diri</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Nama <span class="font-normal text-slate-400">(opsional)</span></span>
                        <input name="name" value="{{ old('name') }}" maxlength="100" placeholder="Boleh dikosongkan"
                               class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none">
                        <span class="mt-1 block text-xs text-slate-500">Mengisi nama memudahkan Pak Andy menindaklanjuti, tetapi tidak wajib.</span>
                    </label>
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Divisi <span class="text-red-600">*</span></span>
                        <select name="division"
                                class="mt-1 w-full rounded-lg border px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none {{ $errors->has('division') ? 'border-red-400' : 'border-stone-300' }}">
                            <option value="">Pilih divisi</option>
                            @foreach (config('survei.divisions') as $div)
                                <option value="{{ $div }}" @selected(old('division') === $div)>{{ $div }}</option>
                            @endforeach
                        </select>
                        @error('division') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>
            </div>
        @endif

        @foreach ($day['questions'] as $i => $q)
            @php
                $name = "answers[{$q['id']}]";
                $key = "answers.{$q['id']}";
                $old = old($key);
                $hasError = $errors->has($key);
            @endphp
            <fieldset class="rounded-2xl border bg-white p-5 shadow-sm {{ $hasError ? 'border-red-300 ring-1 ring-red-200' : 'border-stone-200' }}">
                <legend class="sr-only">Pertanyaan {{ $i + 1 }}</legend>
                <p class="flex gap-3 font-medium text-slate-900">
                    <span class="text-teal-700">{{ $i + 1 }}.</span>
                    <span>
                        {{ $q['text'] }}
                        @if ($q['type'] === 'text') <span class="font-normal text-slate-400">(opsional)</span> @endif
                    </span>
                </p>

                <div class="mt-4">
                    @switch($q['type'])
                        @case('likert')
                            <div class="grid grid-cols-5 gap-1.5 sm:gap-2">
                                @foreach (config('survei.likert_labels') as $val => $label)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="{{ $name }}" value="{{ $val }}" class="peer sr-only" @checked((string) $old === (string) $val)>
                                        <span class="flex h-full flex-col items-center gap-1 rounded-lg border border-stone-300 px-1 py-2 text-center transition peer-checked:border-teal-700 peer-checked:bg-teal-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-teal-600/40 hover:border-teal-500">
                                            <span class="text-lg font-bold">{{ $val }}</span>
                                            <span class="text-[11px] leading-tight sm:text-xs">{{ $label }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('choice')
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($q['options'] as $opt)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-stone-300 px-3 py-2.5 hover:border-teal-500 has-[:checked]:border-teal-700 has-[:checked]:bg-teal-50">
                                        <input type="radio" name="{{ $name }}" value="{{ $opt }}" class="size-4 accent-teal-700" @checked($old === $opt)>
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('multi')
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($q['options'] as $opt)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-stone-300 px-3 py-2.5 hover:border-teal-500 has-[:checked]:border-teal-700 has-[:checked]:bg-teal-50">
                                        <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}" class="size-4 accent-teal-700" @checked(in_array($opt, (array) $old, true))>
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        @case('contact')
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach (['Ya', 'Tidak'] as $opt)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-stone-300 px-3 py-2.5 hover:border-teal-500 has-[:checked]:border-teal-700 has-[:checked]:bg-teal-50">
                                        <input type="radio" name="{{ $name }}" value="{{ $opt }}" class="size-4 accent-teal-700" @checked($old === $opt)>
                                        <span>{{ $opt }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <label class="mt-3 block">
                                <span class="text-sm text-slate-600">Jika memilih <strong>Ya</strong> dan tidak mengisi nama di Hari 1, tuliskan kontak pribadi (No. WA atau email). Opsional.</span>
                                <input name="answers[{{ $q['id'] }}_kontak]" value="{{ old("answers.{$q['id']}_kontak") }}" maxlength="150"
                                       class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none">
                            </label>
                            @break

                        @case('text')
                            <textarea name="{{ $name }}" rows="3" maxlength="2000" placeholder="Tulis jawaban Anda di sini (boleh dikosongkan)"
                                      class="w-full rounded-lg border border-stone-300 px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none">{{ $old }}</textarea>
                            @break
                    @endswitch
                </div>

                @error($key) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </fieldset>
        @endforeach

        @if (! empty($day['show_help']))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-900">
                {{ config('survei.help_text') }}
            </div>
        @endif

        <div class="flex flex-col-reverse items-stretch gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route('survei.index') }}" class="text-center text-sm text-slate-500 hover:text-slate-700">Batal</a>
            <button class="rounded-lg bg-teal-700 px-6 py-3 font-semibold text-white hover:bg-teal-800">Kirim jawaban Hari {{ $dayNumber }}</button>
        </div>
    </form>
</div>
@endsection
