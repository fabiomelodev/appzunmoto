@props(['photo' => null, 'existing' => null, 'removed' => false])
{{--
    Optional location-photo picker. Binds to the parent Livewire component's
    `photo` (temp upload) and calls `clearPhoto()` to drop it. Shows the newly
    selected file, the existing saved photo, or a placeholder.
--}}
@php
    $hasNew = $photo && \Illuminate\Support\Str::startsWith((string) $photo->getMimeType(), 'image/');
    $showsImage = $hasNew || ($existing && ! $removed);
@endphp
<x-ui.field label="Foto do local (opcional)">
    <div class="flex items-center gap-3">
        @if ($hasNew)
            <img src="{{ $photo->temporaryUrl() }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-border/60" />
        @elseif ($existing && ! $removed)
            <img src="{{ $existing }}" alt="" class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-border/60" />
        @else
            <span class="grid h-16 w-16 shrink-0 place-items-center rounded-xl bg-secondary text-muted-foreground">
                <x-ui.icon name="camera" class="h-5 w-5" />
            </span>
        @endif
        <div class="flex flex-col items-start gap-1.5">
            <label class="tap cursor-pointer rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-semibold transition hover:text-primary">
                <input type="file" accept="image/*" wire:model="photo" class="hidden" />
                <span wire:loading.remove wire:target="photo">{{ $showsImage ? 'Trocar foto' : 'Escolher foto' }}</span>
                <span wire:loading wire:target="photo">Enviando…</span>
            </label>
            @if ($showsImage)
                <button type="button" wire:click="clearPhoto" class="text-[11px] font-medium text-destructive">Remover foto</button>
            @endif
        </div>
    </div>
    @error('photo') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
</x-ui.field>
