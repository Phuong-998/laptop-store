<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\SpecController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ImportReceiptController;
use App\Http\Controllers\Admin\ExportReceiptController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\CatalogController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ReviewController as ShopReviewController;
use App\Http\Controllers\Shop\AccountController;

Route::get('/login', [AuthController::class, 'formLogin'])->name('login');
Route::post('/login', [AuthController::class, 'handleLogin'])->name('handle-login');
Route::get('/register', [AuthController::class, 'formRegister'])->name('register');
Route::post('/register', [AuthController::class, 'handleRegister'])->name('handle-register');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::middleware('admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    Route::get('/category', [CategoryController::class, 'index'])->name('admin.category');
    Route::post('/category', [CategoryController::class, 'store'])->name('admin.category.store');
    Route::post('/category/{id}/update', [CategoryController::class, 'update'])->name('admin.category.update');
    Route::post('/category/{id}',[CategoryController::class,'delete'])->name('admin.category.delete');

    Route::get('/brand',[BrandController::class,'index'])->name('admin.brand');
    Route::post('/brand',[BrandController::class,'store'])->name('admin.brand.store');
    Route::post('/brand/{id}/update',[BrandController::class,'updateBrand'])->name('admin.brand.update');
    Route::post('/brand/{id}',[BrandController::class,'delete'])->name('admin.brand.delete');

    Route::get('/products',[ProductController::class,'index'])->name('admin.products');
    Route::post('/product',[ProductController::class,'store'])->name('admin.product.store');
    Route::post('/product/{id}/update',[ProductController::class,'update'])->name('admin.product.update');
    Route::post('/product/{id}',[ProductController::class,'delete'])->name('admin.product.delete');

    Route::get('/coupons',[CouponController::class,'index'])->name('admin.coupons');
    Route::post('/coupon',[CouponController::class,'store'])->name('admin.coupon.store');
    Route::post('/coupon/{id}/update',[CouponController::class,'update'])->name('admin.coupon.update');
    Route::post('/coupon/{id}',[CouponController::class,'delete'])->name('admin.coupon.delete');

    Route::get('/shipping',[ShippingController::class,'index'])->name('admin.shipping');
    Route::post('/shipping',[ShippingController::class,'store'])->name('admin.shipping.store');
    Route::post('/shipping/{id}/update',[ShippingController::class,'update'])->name('admin.shipping.update');
    Route::post('/shipping/{id}',[ShippingController::class,'delete'])->name('admin.shipping.delete');

    Route::get('/specs',[SpecController::class,'index'])->name('admin.specs');
    Route::post('/spec',[SpecController::class,'store'])->name('admin.spec.store');
    Route::post('/spec/{id}/update',[SpecController::class,'update'])->name('admin.spec.update');
    Route::post('/spec/{id}',[SpecController::class,'delete'])->name('admin.spec.delete');

    Route::get('/users',[UserController::class,'index'])->name('admin.users');
    Route::post('/user',[UserController::class,'store'])->name('admin.user.store');
    Route::post('/user/{id}/update',[UserController::class,'update'])->name('admin.user.update');
    Route::post('/user/{id}',[UserController::class,'delete'])->name('admin.user.delete');

    Route::get('/inventory',[InventoryController::class,'index'])->name('admin.inventory');
    Route::get('/inventory/{id}/history',[InventoryController::class,'history'])->name('admin.inventory.history');

    Route::get('/import-receipts',[ImportReceiptController::class,'index'])->name('admin.imports');
    Route::post('/import-receipt/product',[ImportReceiptController::class,'storeProduct'])->name('admin.import.product.store');
    Route::post('/import-receipt',[ImportReceiptController::class,'store'])->name('admin.import.store');
    Route::get('/import-receipt/{id}',[ImportReceiptController::class,'show'])->name('admin.import.show');
    Route::post('/import-receipt/{id}/confirm',[ImportReceiptController::class,'confirm'])->name('admin.import.confirm');
    Route::post('/import-receipt/{id}',[ImportReceiptController::class,'destroy'])->name('admin.import.delete');

    Route::get('/export-receipts',[ExportReceiptController::class,'index'])->name('admin.exports');
    Route::post('/export-receipt',[ExportReceiptController::class,'store'])->name('admin.export.store');
    Route::get('/export-receipt/{id}',[ExportReceiptController::class,'show'])->name('admin.export.show');
    Route::post('/export-receipt/{id}/confirm',[ExportReceiptController::class,'confirm'])->name('admin.export.confirm');
    Route::post('/export-receipt/{id}',[ExportReceiptController::class,'destroy'])->name('admin.export.delete');

    Route::get('/orders',[OrderController::class,'index'])->name('admin.orders');
    Route::post('/order',[OrderController::class,'store'])->name('admin.order.store');
    Route::get('/order/{id}',[OrderController::class,'show'])->name('admin.order.show');
    Route::post('/order/{id}/status',[OrderController::class,'updateStatus'])->name('admin.order.status');
    Route::post('/order/{id}',[OrderController::class,'destroy'])->name('admin.order.delete');

    Route::get('/reviews',[ReviewController::class,'index'])->name('admin.reviews');
    Route::post('/review',[ReviewController::class,'store'])->name('admin.review.store');
    Route::post('/review/{id}/update',[ReviewController::class,'update'])->name('admin.review.update');
    Route::post('/review/{id}/toggle',[ReviewController::class,'toggle'])->name('admin.review.toggle');
    Route::post('/review/{id}',[ReviewController::class,'delete'])->name('admin.review.delete');

});
/*
|--------------------------------------------------------------------------
| Storefront (khách hàng)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('shop.home');
Route::get('/san-pham', [CatalogController::class, 'index'])->name('shop.products');
Route::get('/san-pham/{slug}', [CatalogController::class, 'show'])->name('shop.product');

Route::get('/gio-hang', [CartController::class, 'index'])->name('shop.cart');
Route::post('/gio-hang/them', [CartController::class, 'add'])->name('shop.cart.add');
Route::post('/gio-hang/cap-nhat', [CartController::class, 'update'])->name('shop.cart.update');
Route::post('/gio-hang/xoa', [CartController::class, 'remove'])->name('shop.cart.remove');

Route::get('/thanh-toan', [CheckoutController::class, 'index'])->name('shop.checkout');
Route::post('/thanh-toan/ap-ma', [CheckoutController::class, 'applyCoupon'])->name('shop.checkout.coupon');
Route::post('/thanh-toan', [CheckoutController::class, 'place'])->name('shop.checkout.place');
Route::get('/dat-hang-thanh-cong/{code}', [CheckoutController::class, 'success'])->name('shop.success');

Route::middleware('checkLogin')->group(function () {
    Route::get('/tai-khoan', [AccountController::class, 'index'])->name('shop.account');
    Route::post('/tai-khoan', [AccountController::class, 'updateProfile'])->name('shop.account.update');
    Route::get('/tai-khoan/don-hang', [AccountController::class, 'orders'])->name('shop.account.orders');
    Route::get('/tai-khoan/don-hang/{code}', [AccountController::class, 'orderDetail'])->name('shop.account.order');
    Route::post('/san-pham/{id}/danh-gia', [ShopReviewController::class, 'store'])->name('shop.review.store');
});

Route::get('/forgot-password', [AuthController::class, 'formForgotPassword'])->name('forgot-password');
Route::post('/forgot-password', [AuthController::class, 'handleForgotPassword'])->name('handle-forgot-password');
Route::get('/reset-password/{token}', [AuthController::class, 'formResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'handleResetPassword'])->name('password.update');
