{{--
    "Instale o app do ZunMoto": invitation + tutorial (Android / iPhone / Computador). The logic lives in the
    Alpine store `install` (resources/js/pwa-install.js): it opens by itself once in a while, from the
    Menu entry, and is gone for good once the app is installed (standalone / appinstalled).
--}}
@php
    $tabs = ['android' => 'Android', 'ios' => 'iPhone', 'desktop' => 'Computador'];
@endphp
<div x-data x-init="$store.install.maybeAutoOpen()" x-cloak
    x-show="$store.install.open && ! $store.install.installed"
    x-transition.opacity
    x-on:keydown.escape.window="$store.install.open && $store.install.dismiss()"
    class="fixed inset-0 z-[80] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
    x-on:click.self="$store.install.dismiss()" role="dialog" aria-modal="true" aria-labelledby="install-app-title">
    <div class="relative max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-3xl border border-border bg-card p-6 shadow-2xl">
        <button type="button" x-on:click="$store.install.dismiss()" aria-label="Fechar"
            class="tap absolute right-4 top-4 grid h-8 w-8 place-items-center rounded-full text-muted-foreground transition hover:text-foreground">
            <x-ui.icon name="x" class="h-5 w-5" />
        </button>

        <h2 id="install-app-title" class="mt-6 font-display text-2xl font-bold tracking-tight">Instale o app do ZunMoto</h2>
        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
            É grátis e não precisa de loja de aplicativos: o app é instalado direto pelo navegador e abre em tela cheia, como qualquer outro.
        </p>

        {{-- Platform tabs (the visitor's own platform is preselected) --}}
        <div role="tablist" class="mt-5 grid grid-cols-3 gap-1 rounded-full border border-border bg-input p-1">
            @foreach ($tabs as $key => $label)
                <button type="button" role="tab" x-on:click="$store.install.tab = '{{ $key }}'"
                    :aria-selected="($store.install.tab === '{{ $key }}').toString()"
                    :class="$store.install.tab === '{{ $key }}' ? 'bg-primary text-primary-foreground shadow' : 'text-foreground hover:bg-surface-elevated'"
                    class="tap rounded-full px-2 py-2.5 text-xs font-semibold transition">{{ $label }}</button>
            @endforeach
        </div>

        {{-- Steps --}}
        <ol class="mt-5 space-y-4 text-sm leading-relaxed">
            <template x-if="$store.install.tab === 'android'">
                <div class="space-y-4">
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                        <span>Abra o ZunMoto no <strong>Chrome</strong> (ou no navegador do seu celular).</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                        <span>Toque no menu <strong>⋮</strong> do navegador e escolha <strong>Instalar app</strong> (ou <strong>Adicionar à tela inicial</strong>).</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                        <span>Confirme em <strong>Instalar</strong>. O ícone do ZunMoto aparece na sua tela inicial.</span></li>
                </div>
            </template>
            <template x-if="$store.install.tab === 'ios'">
                <div class="space-y-4">
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                        <span>Abra o ZunMoto no <strong>Safari</strong>. No iPhone, outros navegadores não conseguem instalar.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                        <span>Toque no botão <strong>Compartilhar</strong> (o quadrado com uma seta para cima).</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                        <span>Role e escolha <strong>Adicionar à Tela de Início</strong>, depois confirme em <strong>Adicionar</strong>. As notificações no iPhone só funcionam com o app instalado.</span></li>
                </div>
            </template>
            <template x-if="$store.install.tab === 'desktop'">
                <div class="space-y-4">
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">1</span>
                        <span>Use o <strong>Chrome</strong> ou o <strong>Edge</strong>.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">2</span>
                        <span>Clique no ícone de <strong>instalar</strong> no fim da barra de endereço, ou abra o menu <strong>⋮</strong> e escolha <strong>Instalar ZunMoto</strong>.</span></li>
                    <li class="flex gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-primary-foreground">3</span>
                        <span>Confirme em <strong>Instalar</strong>. O app abre em janela própria e fica no seu menu de aplicativos.</span></li>
                </div>
            </template>
        </ol>

        {{-- One tap when the browser allows it; otherwise the tutorial above is the way --}}
        <button type="button" x-on:click="$store.install.canPrompt ? $store.install.install() : $store.install.dismiss()"
            class="tap glow-orange mt-6 flex h-14 w-full items-center justify-center gap-2 rounded-full bg-primary text-base font-bold text-primary-foreground transition hover:bg-primary/90">
            <span x-text="$store.install.canPrompt ? 'Instalar agora' : 'Entendi'"></span>
            <x-ui.icon name="chevron-right" class="h-4 w-4" x-show="$store.install.canPrompt" />
        </button>
        <button type="button" x-on:click="$store.install.dismiss()" x-show="$store.install.canPrompt"
            class="tap mt-2 w-full py-2 text-center text-xs font-semibold text-muted-foreground transition hover:text-foreground">Agora não</button>

        <p class="mt-3 text-center text-[11px] leading-relaxed text-muted-foreground">
            Se o botão de instalar não aparecer, é porque o app já está instalado ou o navegador não oferece a instalação.
        </p>
    </div>
</div>
