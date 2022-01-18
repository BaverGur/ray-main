<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers;

Route::get('sellerPriceCheck','App\Http\Controllers\SaveExportedProductController@SaveEmployeeRecord');


Route::get('/change-locale/{locale}', function ($locale) {
    Session::put('locale', $locale);
    return redirect()->back();
})->name('change_locale');

Route::any('/login', [Controllers\LoginController::class, 'index'])->name('login');

Route::get('/logout', function () {
    Session::flush();
    return redirect()->route('login');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [Controllers\DashboardController::class, 'index'])->name('home');
    Route::any('/settings', [Controllers\SettingsController::class, 'index'])->name('settings');
    Route::any('/shipping-settings/save', [Controllers\SettingsController::class, 'save'])->name('shipping-settings.save');
    Route::any('/shipping-settings/delete', [Controllers\SettingsController::class, 'delete'])->name('shipping-settings.delete');

    Route::get('/users', [Controllers\UsersController::class, 'index'])->middleware('auth.admin')->name('users.search');
    Route::any('/users/save', [Controllers\UsersController::class, 'save'])->middleware('auth.admin')->name('users.save');
    Route::any('/users/delete', [Controllers\UsersController::class, 'delete'])->middleware('auth.admin')->name('users.delete');

    Route::get('/system-categories/search', [Controllers\CategoriesController::class, 'ajaxSearch'])->name('categories.ajax_search');
    Route::get('/rakuten-genre/search', [Controllers\RakutenGenreController::class, 'ajaxSearch'])->name('rakuten_genre.ajax_search');
    Route::get('/categories', [Controllers\UserCategoriesController::class, 'index'])->name('user_categories.search');
    Route::any('/categories/save', [Controllers\UserCategoriesController::class, 'save'])->name('user_categories.save');
    Route::any('/categories/delete', [Controllers\UserCategoriesController::class, 'delete'])->name('user_categories.delete');

    Route::get('/exported-products', [Controllers\ExportedProductsController::class, 'index'])->name('exported_products.search');
    Route::any('/exported-products/save', [Controllers\SaveExportedProductController::class, 'index'])->name('exported_products.save');
    Route::any('/exported-products/delete', [Controllers\ExportedProductsController::class, 'delete'])->name('exported_products.delete');
    Route::get('/exported-products/export', [Controllers\ExportedProductsController::class, 'export'])->name('exported_products.export');
   
    
    Route::any('/import/product-link', [Controllers\YahooProductsController::class, 'importByProductLink'])->name('import.product-link');
    Route::any('/import/search-link', [Controllers\YahooProductsController::class, 'importBySearchLink'])->name('import.search-link');
    Route::any('/import/keyword', [Controllers\YahooProductsController::class, 'importByKeyword'])->name('import.keyword');

    Route::get('/my-products', [Controllers\MyProduct\ListController::class, 'index'])->name('my_products.search');
    Route::any('/my-products/auto-stock-setting', [Controllers\MyProduct\AutoStockSettingController::class, 'index'])->name('my_products.auto_stock_setting');
    Route::post('/my-products/update-shipping-fee', [Controllers\MyProduct\UpdateShippingFeeController::class, 'index'])->name('my_products.update_shipping_fee');
    Route::get('/my-products/calculate-profit', [Controllers\MyProduct\CalculateProfitController::class, 'index'])->name('my_products.calculate_profit');
    Route::post('/my-products/update-price', [Controllers\MyProduct\UpdatePriceController::class, 'index'])->name('my_products.update_price');

    Route::get('/minimum-profits', [Controllers\MinimumProfitController::class, 'index'])->name('minimum_profits.search');
    Route::any('/minimum-profits/save', [Controllers\MinimumProfitController::class, 'save'])->name('minimum_profits.save');
    Route::any('/minimum-profits/delete', [Controllers\MinimumProfitController::class, 'delete'])->name('minimum_profits.delete');
});
