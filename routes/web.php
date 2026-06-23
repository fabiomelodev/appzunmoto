<?php
// routes/web.php

use App\Livewire\Addresses\Choose as AddressesChoose;
use App\Livewire\Auth\Login;
use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\Menu;
use App\Livewire\Notifications\Page as NotificationsPage;
use App\Livewire\ProfilePage;
use App\Livewire\Settings;
use App\Livewire\Shifts\Create as ShiftsCreate;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Livewire\Shifts\Show as ShiftsShow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ── Public (guest) ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

// ── Authenticated app ────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::redirect('/', '/shifts');

    // Shifts
    Route::get('/shifts', ShiftsIndex::class)->name('shifts.index');
    Route::get('/shifts/new', ShiftsCreate::class)->name('shifts.create');     // static before {id}
    Route::get('/shifts/{id}/edit', ShiftsCreate::class)->name('shifts.edit');
    Route::get('/shifts/{id}', ShiftsShow::class)->name('shifts.show');

    // Addresses
    Route::get('/addresses/choose', AddressesChoose::class)->name('addresses.choose');

    // Partnerships / chats & notifications
    Route::get('/chats', ChatsIndex::class)->name('chats.index');
    Route::get('/chats/{id}', ChatsShow::class)->name('chats.show');
    Route::get('/notifications', NotificationsPage::class)->name('notifications');

    // Account
    Route::get('/menu', Menu::class)->name('menu');
    Route::get('/profile', ProfilePage::class)->name('profile');
    Route::get('/settings', Settings::class)->name('settings');

    // Placeholders — rebuilt in later phases.
    Route::view('/map', 'placeholder', ['title' => 'Mapa'])->name('map');
    Route::view('/vehicle', 'placeholder', ['title' => 'Veículo'])->name('vehicle');
    Route::view('/history', 'placeholder', ['title' => 'Histórico'])->name('history');
    Route::view('/documents', 'placeholder', ['title' => 'Documentos'])->name('documents');
    Route::view('/addresses', 'placeholder', ['title' => 'Endereços'])->name('addresses');
    Route::view('/help', 'placeholder', ['title' => 'Ajuda'])->name('help');
});
