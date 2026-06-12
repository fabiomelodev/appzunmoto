<div class="flex min-h-dvh flex-col px-6 py-10">
    <div class="flex flex-1 flex-col justify-center">

        {{-- Logo + título --}}
        <div class="mb-8 flex flex-col items-center text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[#f97316]/15">
                <svg class="h-10 w-10 text-[#f97316]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
            </div>
            <h1 class="mt-4 text-3xl font-bold tracking-tight">
                Moto<span class="text-[#f97316]">Reserva</span>
            </h1>
            <p class="mt-2 max-w-xs text-sm text-[#737373]">
                Conectando motoboys e comércios para nenhuma entrega ficar para trás.
            </p>
        </div>

        {{-- Tabs Entrar / Criar conta --}}
        <div class="mb-4 flex rounded-full bg-[#262626] p-1 text-sm">
            <button wire:click="setMode('signin')"
                    class="flex-1 rounded-full py-2 font-medium transition tap
                           {{ $mode === 'signin' ? 'bg-[#1a1a1a] text-[#f5f5f5] shadow' : 'text-[#737373]' }}">
                Entrar
            </button>
            <button wire:click="setMode('signup')"
                    class="flex-1 rounded-full py-2 font-medium transition tap
                           {{ $mode === 'signup' ? 'bg-[#1a1a1a] text-[#f5f5f5] shadow' : 'text-[#737373]' }}">
                Criar conta
            </button>
        </div>

        {{-- Erro geral --}}
        @if($erro)
            <div class="mb-3 rounded-xl border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {{ $erro }}
            </div>
        @endif

        {{-- Flash de sucesso --}}
        @if(session('success'))
            <div class="mb-3 rounded-xl border border-green-500/40 bg-green-500/10 px-4 py-3 text-sm text-green-400">
                {{ session('success') }}
            </div>
        @endif

        {{-- Formulário --}}
        <form wire:submit="submit" class="space-y-3 rounded-2xl border border-[#2a2a2a] bg-[#1a1a1a] p-5">

            @if($mode === 'signup')
                {{-- Nome --}}
                <div class="space-y-1.5">
                    <label class="text-xs text-[#737373]">Nome completo</label>
                    <input wire:model="nome" type="text" placeholder="João da Silva" />
                    @error('nome') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- CPF --}}
                <div class="space-y-1.5">
                    <label class="text-xs text-[#737373]">CPF</label>
                    <input wire:model="cpf" type="text" inputmode="numeric" placeholder="000.000.000-00" maxlength="14" />
                    @error('cpf') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Data de nascimento --}}
                <div class="space-y-1.5">
                    <label class="text-xs text-[#737373]">Data de nascimento</label>
                    <input wire:model="nasc" type="text" inputmode="numeric" placeholder="DD/MM/AAAA" maxlength="10" />
                    @error('nasc') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Telefone --}}
                <div class="space-y-1.5">
                    <label class="text-xs text-[#737373]">Telefone / WhatsApp</label>
                    <input wire:model="tel" type="tel" placeholder="(11) 9 9999-0000" />
                    @error('tel') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- Endereço --}}
                <div class="grid grid-cols-[1fr_90px] gap-2">
                    <div class="space-y-1.5">
                        <label class="text-xs text-[#737373]">Rua</label>
                        <input wire:model="rua" type="text" placeholder="Av. Paulista" />
                        @error('rua') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-[#737373]">Número</label>
                        <input wire:model="numero" type="text" placeholder="100" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1.5">
                        <label class="text-xs text-[#737373]">Bairro</label>
                        <input wire:model="bairro" type="text" placeholder="Centro" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs text-[#737373]">Cidade</label>
                        <input wire:model="cidade" type="text" placeholder="São Paulo - SP" />
                    </div>
                </div>
            @endif

            {{-- E-mail --}}
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">E-mail</label>
                <input wire:model="email" type="email" placeholder="voce@email.com" />
                @error('email') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- Senha --}}
            <div class="space-y-1.5">
                <label class="text-xs text-[#737373]">Senha</label>
                <div class="relative">
                    <input wire:model="senha"
                           type="{{ $showSenha ? 'text' : 'password' }}"
                           placeholder="••••••"
                           minlength="6"
                           class="pr-10" />
                    <button type="button" wire:click="$toggle('showSenha')"
                            class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-[#737373] hover:text-[#f5f5f5]">
                        @if($showSenha)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                        @else
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        @endif
                    </button>
                </div>
                @error('senha') <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p> @enderror
            </div>

            @if($mode === 'signup')
                {{-- Confirmar senha --}}
                <div class="space-y-1.5">
                    <label class="text-xs text-[#737373]">Confirmar senha</label>
                    <div class="relative">
                        <input wire:model="senha2"
                               type="{{ $showSenha2 ? 'text' : 'password' }}"
                               placeholder="••••••"
                               minlength="6"
                               class="pr-10 {{ ($senha2 && $senha !== $senha2) ? 'border-red-500' : '' }}" />
                        <button type="button" wire:click="$toggle('showSenha2')"
                                class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-[#737373] hover:text-[#f5f5f5]">
                            @if($showSenha2)
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                            @else
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            @endif
                        </button>
                    </div>
                    @if($senha2 && $senha !== $senha2)
                        <p class="mt-1 text-[11px] font-medium text-red-400">As senhas não coincidem.</p>
                    @endif
                </div>
            @endif

            {{-- Botão submit --}}
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="tap flex w-full items-center justify-center gap-2 rounded-xl bg-[#f97316] px-4 py-3 font-semibold text-white transition hover:bg-[#ea6c0a] disabled:opacity-50 glow-orange">
                <span wire:loading wire:target="submit" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                <svg wire:loading.remove wire:target="submit" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                {{ $mode === 'signup' ? 'Criar conta' : 'Entrar' }}
            </button>
        </form>

        @if($mode === 'signup')
            <p class="mt-4 text-center text-[11px] text-[#737373]">
                Ao criar conta você concorda com os Termos e a Política de Privacidade.
            </p>
        @endif

    </div>
</div>