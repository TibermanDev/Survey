@props(['name' => 'key', 'label' => 'Kunci', 'autofocus' => false, 'hint' => null])
<label class="mt-4 block">
    <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
    <input type="password" name="{{ $name }}" required @if ($autofocus) autofocus @endif autocomplete="off"
           class="mt-1 w-full rounded-lg border px-3 py-2 focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20 focus:outline-none {{ $errors->has($name) ? 'border-red-400' : 'border-stone-300' }}">
    @if ($hint) <span class="mt-1 block text-xs text-slate-500">{{ $hint }}</span> @endif
    @error($name) <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
</label>
