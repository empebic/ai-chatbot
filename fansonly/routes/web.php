<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware(['auth'])->group(function () {
    Route::resource('posts', \App\Http\Controllers\PostController::class)->only(['index','create','store','show']);
    Route::get('/feed', [\App\Http\Controllers\FeedController::class, 'index'])->name('feed');
    Route::get('/@{username}/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'create'])->name('subscribe.create');
    Route::post('/@{username}/subscribe', [\App\Http\Controllers\SubscriptionController::class, 'store'])->name('subscribe.store');
});

Route::post('/webhooks/stripe', [\App\Http\Controllers\WebhookController::class, 'handleStripe'])->name('webhooks.stripe');
Route::middleware(['auth'])
    ->prefix('creator')
    ->group(function () {
        Route::get('settings', [\App\Http\Controllers\CreatorController::class, 'edit'])->name('creator.settings');
        Route::put('settings', [\App\Http\Controllers\CreatorController::class, 'update'])->name('creator.update');
    });

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
