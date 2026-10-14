@props(['photo' => null, 'existing' => null, 'removed' => false])
{{--
    Optional location-photo picker. Binds to the parent Livewire component's
    `photo` (temp upload) and calls `clearPhoto()` to drop it.

    The preview of a freshly picked file is drawn in the browser (URL.createObjectURL),
    NOT from Livewire's signed `preview-file` URL (TemporaryUploadedFile::temporaryUrl()):
    that route doesn't survive every shared-hosting setup (Hostinger), where the upload
    itself works but the preview image never loads — same reason the profile photo is
    shown from public storage after saving instead. The saved photo is still the
    server-side one, shown from its public URL.
--}}
@php
    $hasNew = (bool) $photo;
    $showsImage = $hasNew || ($existing && ! $removed);
@endphp
<x-ui.field label="Foto do local (opcional)">
    <div class="flex items-center gap-3"
        x-data="{ preview: null }"
        x-init="$wire.$watch('photo', (value) => { if (! value && preview) { URL.revokeObjectURL(preview); preview = null; } })">
        {{-- Freshly picked file (client-side preview) --}}
        <img x-show="preview" x-cloak :src="preview" alt="" x-on:error="preview = null"
            class="h-16 w-16 shrink-0 rounded-xl object-cover ring-1 ring-border/60" />

        {{-- Otherwise the saved photo, or a placeholder --}}
        <div x-show="! preview" class="shrink-0">
            @if ($existing && ! $removed)
                <img src="{{ $existing }}" alt="" class="h-16 w-16 rounded-xl object-cover ring-1 ring-border/60" />
            @else
                <span class="grid h-16 w-16 place-items-center rounded-xl bg-secondary text-muted-foreground">
                    <x-ui.icon name="camera" class="h-5 w-5" />
                </span>
            @endif
        </div>

        <div class="flex flex-col items-start gap-1.5">
            <label class="tap cursor-pointer rounded-lg border border-border bg-surface px-3 py-1.5 text-xs font-semibold transition hover:text-primary">
                {{-- Explicit types: iOS converts HEIC to JPEG when picking, which "image/*" doesn't trigger. --}}
                <input type="file" accept="image/jpeg,image/png,image/webp" wire:model="photo" class="hidden"
                    x-on:change="
                        const file = $event.target.files[0];
                        if (preview) { URL.revokeObjectURL(preview); preview = null; }
                        if (file && file.type.startsWith('image/')) { preview = URL.createObjectURL(file); }
                    " />
                <span wire:loading.remove wire:target="photo">{{ $showsImage ? 'Trocar foto' : 'Escolher foto' }}</span>
                <span wire:loading wire:target="photo">Enviando…</span>
            </label>
            @if ($showsImage)
                <button type="button" wire:click="clearPhoto" x-on:click="if (preview) { URL.revokeObjectURL(preview); preview = null; }"
                    class="text-[11px] font-medium text-destructive">Remover foto</button>
            @endif
        </div>
    </div>
    @error('photo') <p class="mt-1 text-xs font-medium text-destructive">{{ $message }}</p> @enderror
</x-ui.field>
