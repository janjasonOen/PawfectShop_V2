<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\HomeController;

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminBookingsController;
use App\Http\Controllers\AdminOrdersController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminItemsController;
use App\Http\Controllers\AdminCategoriesController;
use App\Http\Controllers\AdminSchedulesController;
use App\Http\Controllers\AdminUsersController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerOrdersController;
use App\Http\Controllers\CustomerBookingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/item/{id}', [ItemController::class, 'show'])->whereNumber('id')->name('item.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::match(['GET', 'POST'], '/cart/action', [CartController::class, 'action'])->name('cart.action');

// Admin
Route::get('/admin', function () {
	return redirect()->route('admin.dashboard');
})->name('admin.home');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::get('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

Route::match(['get', 'post'], '/admin/items', [AdminItemsController::class, 'index'])->name('admin.items');
Route::match(['get', 'post'], '/admin/categories', [AdminCategoriesController::class, 'index'])->name('admin.categories');
Route::match(['get', 'post'], '/admin/schedules', [AdminSchedulesController::class, 'index'])->name('admin.schedules');
Route::match(['get', 'post'], '/admin/users', [AdminUsersController::class, 'index'])->name('admin.users');

Route::match(['get', 'post'], '/admin/orders', [AdminOrdersController::class, 'index'])->name('admin.orders');
Route::match(['get', 'post'], '/admin/orders/{id}', [AdminOrdersController::class, 'detail'])->name('admin.order_detail');

Route::match(['get', 'post'], '/admin/bookings', [AdminBookingsController::class, 'index'])->name('admin.bookings');
Route::match(['get', 'post'], '/admin/bookings/{id}', [AdminBookingsController::class, 'detail'])->name('admin.booking_detail');

Route::post('/auth/check-email', [CustomerAuthController::class, 'checkEmail'])->name('auth.checkEmail');
Route::post('/auth/customer', [CustomerAuthController::class, 'customerAuth'])->name('auth.customer');
Route::get('/auth/logout', [CustomerAuthController::class, 'logout'])->name('auth.logout');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'place'])->name('checkout.place');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');

Route::post('/payment-proof/upload', [PaymentProofController::class, 'upload'])->name('payment_proof.upload');

Route::match(['get', 'post'], '/customer/addresses', [CustomerAddressController::class, 'index'])->name('customer.addresses');

Route::get('/customer/orders', [CustomerOrdersController::class, 'index'])->name('customer.orders');
Route::get('/customer/orders/{id}', [CustomerOrdersController::class, 'show'])->name('customer.order_detail');

Route::get('/customer/bookings', [CustomerBookingsController::class, 'index'])->name('customer.bookings');
Route::get('/customer/bookings/{id}', [CustomerBookingsController::class, 'show'])->name('customer.booking_detail');
