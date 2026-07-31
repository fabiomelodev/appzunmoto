@props(['label', 'hint', 'doc' => null, 'model'])
@php
    $status = $doc?->status ?? 'pending';
    [$statusLabel, $statusIcon, $statusColor, $statusBg] = match ($status) {
        'approved' => ['Aprovado', 'shield-check', 'text-success', 'bg-success/15'],
        'review' => ['Em análise', 'clock', 'text-primary', 'bg-primary/15'],
        'submitted' => ['Enviado', 'check-circle', 'text-primary', 'bg-primary/15'],
        'rejected' => ['Recusado', 'x-circle', 'text-destructive', 'bg-destructive/15'],
        default => ['Pendente', 'file-text', 'text-muted-foreground', 'bg-surface-elevated'],
    };
@endphp
<div class="rounded-2xl border border-border/60 bg-card p-4">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <x-ui.icon name="file-text" class="h-4 w-4 text-primary" />
                <p class="text-sm font-semibold text-foreground">{{ $label }}</p>
            </div>
            <p class="mt-0.5 text-[11px] text-muted-foreground">{{ $hint }}</p>
            @if ($doc?->file_name)
                <a href="{{ route('documents.file', $doc) }}" target="_blank"
                    class="mt-1 block truncate text-[11px] text-primary underline">Arquivo: {{ $doc->file_name }}</a>
            @endif
        </div>
        <span class="flex shrink-0 items-center gap-1 rounded-full px-2 py-1 text-[10px] font-semibold {{ $statusBg }} {{ $statusColor }}">
            <x-ui.icon :name="$statusIcon" class="h-3 w-3" /> {{ $statusLabel }}
        </span>
    </div>

    <input type="file" id="doc-{{ $model }}" wire:model="{{ $model }}" accept="image/*,application/pdf" class="hidden" />
    <label for="doc-{{ $model }}"
        class="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-border bg-surface px-3 py-2.5 text-xs font-semibold text-foreground transition hover:border-primary hover:text-primary">
        <x-ui.icon name="upload" class="h-4 w-4" />
        <span wire:loading.remove wire:target="{{ $model }}">{{ $status === 'pending' ? 'Enviar documento' : 'Substituir arquivo' }}</span>
        <span wire:loading wire:target="{{ $model }}">Enviando…</span>
    </label>
</div>
