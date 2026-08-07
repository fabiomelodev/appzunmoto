<x-layouts.app title="Ajuda — ZunMoto">
    <div class="pb-6" x-data="{ open: 0 }">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-primary/25 via-primary/5 to-transparent"></div>
            <div class="relative flex items-center gap-3 px-4 pb-4 pt-6">
                <a href="{{ route('menu') }}" wire:navigate class="grid h-10 w-10 place-items-center rounded-full border border-border/60 bg-surface">
                    <x-ui.icon name="arrow-left" class="h-5 w-5" />
                </a>
                <div>
                    <h1 class="font-display text-xl font-bold leading-tight">Ajuda e Suporte</h1>
                    <p class="text-xs text-muted-foreground">Central de ajuda, FAQ e contato</p>
                </div>
            </div>
        </div>

        <div class="mt-2 space-y-5 px-4">
            @if ($faqs->isNotEmpty())
                <section class="space-y-2">
                    <h2 class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Perguntas frequentes</h2>
                    <div class="space-y-2">
                        @foreach ($faqs as $i => $faq)
                            <div class="rounded-2xl border border-border/60 bg-card">
                                <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                                    class="flex w-full items-center gap-3 p-4 text-left">
                                    <x-ui.icon name="help-circle" class="h-4 w-4 shrink-0 text-primary" />
                                    <span class="flex-1 text-sm font-semibold">{{ $faq->name }}</span>
                                    <x-ui.icon name="chevron-down" class="h-4 w-4 shrink-0 text-muted-foreground transition" x-bind:class="open === {{ $i }} ? 'rotate-180' : ''" />
                                </button>
                                <div x-show="open === {{ $i }}" x-cloak class="border-t border-border/60 px-4 py-3 text-xs text-muted-foreground">{!! $faq->description !!}</div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($contacts->isNotEmpty())
                <section class="space-y-2">
                    <h2 class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Falar com o suporte</h2>
                    <div class="grid grid-cols-1 gap-2">
                        @foreach ($contacts as $contact)
                            <a href="{{ $contact->link }}" class="flex h-12 items-center rounded-xl border border-border/60 bg-surface px-4 text-sm font-medium">
                                <x-ui.icon :name="App\Support\Catalog::CONTACT_TYPE_ICON[$contact->type] ?? 'mail'" class="mr-2 h-4 w-4 text-primary" /> {{ $contact->name }}
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts.app>
