<?php

use App\Http\Controllers\Api\Checkout\CheckoutController;
use App\Http\Controllers\Web\SessionAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/upgrade', fn () => view('checkout.upgrade'))->name('checkout.upgrade');

Route::get('/login', [SessionAuthController::class, 'showLogin'])->name('login');

Route::get('/register', [SessionAuthController::class, 'showRegister'])->name('web.register');
Route::post('/login', [SessionAuthController::class, 'login'])->name('web.login');
Route::post('/register', [SessionAuthController::class, 'register'])->name('web.register.store');
Route::post('/logout', [SessionAuthController::class, 'logout'])->middleware('auth')->name('web.logout');

Route::post('/checkout/premium', [CheckoutController::class, 'redirectToPremiumCheckout'])
    ->middleware('auth')
    ->name('checkout.premium.web');

Route::get('/checkout/success', [CheckoutController::class, 'showCheckoutSuccess'])->name('checkout.success');
Route::get('/checkout/cancel', [CheckoutController::class, 'showCheckoutCancel'])->name('checkout.cancel');
