<?php

// routes/web.php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Livewire\Addresses\Choose as AddressesChoose;
use App\Livewire\Addresses\Index as AddressesIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\History;
use App\Livewire\MapPage;
use App\Livewire\Menu;
use App\Livewire\Notifications\Page as NotificationsPage;
use App\Livewire\ProfilePage;
use App\Livewire\Settings;
use App\Livewire\Shifts\Create as ShiftsCreate;
use App\Livewire\Shifts\Index as ShiftsIndex;
use App\Livewire\Shifts\Show as ShiftsShow;
use App\Livewire\Vehicle;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Faq;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// ── Public (guest) ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');

    // Google OAuth (Laravel Socialite) — full-page redirect + callback.
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
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

    // Vehicle & documents (same screen serves both routes)
    Route::get('/vehicle', Vehicle::class)->name('vehicle');
    Route::get('/documents', Vehicle::class)->name('documents');

    // Serve a private document file to its owner only.
    Route::get('/documents/{document}/file', function (Document $document) {
        abort_unless($document->user_id === Auth::id(), 403);
        abort_unless(
            $document->file_path && Storage::disk('local')->exists($document->file_path),
            404,
        );

        return Storage::disk('local')->response($document->file_path, $document->file_name);
    })->name('documents.file');

    // Addresses (management list)
    Route::get('/addresses', AddressesIndex::class)->name('addresses');

    // Map, history & help
    Route::get('/map', MapPage::class)->name('map');
    Route::get('/history', History::class)->name('history');
    Route::get('/ajuda-e-suporte', function () {
        return view('help', [
            'faqs' => Faq::active()->get(),
            'contacts' => Contact::active()->orderBy('order')->get(),
        ]);
    })->name('help');
});
