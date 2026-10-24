<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderReceiptController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/tienda', [CatalogController::class, 'index'])->name('catalog');
Route::get('/producto/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/ayuda/{page}', [PageController::class, 'show'])->name('pages.show');

Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito', [CartController::class, 'store'])->name('cart.store');
Route::patch('/carrito/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{variant}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/finalizar-compra', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/finalizar-compra', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/pedido/{order}/comprobante', [OrderReceiptController::class, 'store'])->middleware('throttle:10,1')->name('orders.receipt.store');

Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [LoginController::class, 'create'])->name('login');
    Route::post('/ingresar', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/registro', [RegisterController::class, 'create'])->name('register');
    Route::post('/registro', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/salir', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/mi-cuenta', [AccountController::class, 'index'])->name('account.index');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::resource('productos', Admin\ProductController::class)->except('show')
        ->parameters(['productos' => 'product'])->names('products');
    Route::delete('imagenes/{image}', [Admin\ProductImageController::class, 'destroy'])->name('product-images.destroy');
    Route::resource('categorias', Admin\CategoryController::class)->except('show')
        ->parameters(['categorias' => 'category'])->names('categories');
    Route::resource('marcas', Admin\BrandController::class)->except('show')
        ->parameters(['marcas' => 'brand'])->names('brands');
    Route::get('pedidos', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('pedidos/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::patch('pedidos/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::get('pedidos/{order}/comprobante', [Admin\OrderController::class, 'receipt'])->name('orders.receipt');
    Route::get('configuracion', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('configuracion', [Admin\SettingController::class, 'update'])->name('settings.update');
});
