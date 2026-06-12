<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Vagas\Index as VagasIndex;
use App\Livewire\Vagas\Show as VagasShow;
use App\Livewire\Vagas\Nova as VagasNova;
use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\Perfil;

// ──────────────────────────────────────────────
// Rotas públicas (autenticação)
// ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// ──────────────────────────────────────────────
// Rotas protegidas
// ──────────────────────────────────────────────
// Route::middleware('auth')->group(function () {

// Redireciona / para /vagas
Route::get('/', fn() => redirect()->route('vagas.index'));

// Vagas
Route::get('/vagas', VagasIndex::class)->name('vagas.index');
Route::get('/vagas/nova', VagasNova::class)->name('vagas.nova');
Route::get('/vagas/{id}', VagasShow::class)->name('vagas.show');

// Chats
Route::get('/chats', ChatsIndex::class)->name('chats.index');
Route::get('/chats/{id}', ChatsShow::class)->name('chats.show');

// Perfil
Route::get('/perfil', Perfil::class)->name('perfil');

// Mapa (placeholder — adicionar lógica de mapa depois)
Route::get('/mapa', fn() => view('livewire.mapa'))->name('mapa');

// Menu
Route::get('/menu', fn() => view('livewire.menu'))->name('menu');

// Histórico
Route::get('/historico', fn() => view('livewire.historico'))->name('historico');

// Favoritos
Route::get('/favoritos', fn() => view('livewire.favoritos'))->name('favoritos');

// Notificações
Route::get('/notificacoes', fn() => view('livewire.notificacoes'))->name('notificacoes');

// Documentos
Route::get('/documentos', fn() => view('livewire.documentos'))->name('documentos');

// Endereços
Route::get('/enderecos', fn() => view('livewire.enderecos'))->name('enderecos');

// Veículo
Route::get('/veiculo', fn() => view('livewire.veiculo'))->name('veiculo');

// Configurações
Route::get('/configuracoes', fn() => view('livewire.configuracoes'))->name('configuracoes');

// Ajuda
Route::get('/ajuda', fn() => view('livewire.ajuda'))->name('ajuda');

// });
