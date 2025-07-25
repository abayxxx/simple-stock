<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataMaster\CompanyProfileController;
use App\Http\Controllers\DataMaster\EmployeProfileController;
use App\Http\Controllers\DataMaster\ProductController;
use App\Http\Controllers\DataMaster\SalesGroupController;
use App\Http\Controllers\DataMaster\CompanyBranchController;
use App\Http\Controllers\Stocks\StockController;
use App\Http\Controllers\Sales\SalesInvoiceController;
use App\Http\Controllers\Sales\SalesReturnController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('home');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // [DATA MASTER]
    Route::group(['prefix' => 'admin'], function () {
        // *Company Profiles*
        Route::get('company_profiles/datatable', [CompanyProfileController::class, 'datatable'])->name('company_profiles.datatable');
        Route::resource('company_profiles', CompanyProfileController::class);

        // *Products*
        Route::get('products/datatable', [ProductController::class, 'datatable'])->name('products.datatable');
        Route::get('admin/stocks/product-options/{product}', [StockController::class, 'getProductOptions']);

        Route::resource('products', ProductController::class);

        // *Employee Profiles*
        Route::get('employe_profiles/datatable', [EmployeProfileController::class, 'datatable'])->name('employe_profiles.datatable');
        Route::resource('employe_profiles', EmployeProfileController::class);

        // *Sales Groups*
        Route::get('sales_groups/datatable', [SalesGroupController::class, 'datatable'])->name('sales_groups.datatable');
        Route::resource('sales_groups', SalesGroupController::class);

        // *Company Branches*
        Route::get('company_branches/datatable', [CompanyBranchController::class, 'datatable'])->name('company_branches.datatable');
        Route::resource('company_branches', CompanyBranchController::class);

        // *Stocks*
        Route::group(['prefix' => 'stocks'], function () {

            // For AJAX requests
            Route::get('get-sisa-stok/{product_id}', [StockController::class, 'calculateSisaStok'])->name('stock.get_sisa_stok');
            Route::get('product-options/{product}', [StockController::class, 'getProductOptions']);


            Route::get('in', [StockController::class, 'indexIn'])->name('stock.in');
            Route::get('out', [StockController::class, 'indexOut'])->name('stock.out');
            Route::get('delete', [StockController::class, 'indexDestroy'])->name('stock.destroy');

            Route::get('{type}/datatable', [StockController::class, 'datatable'])->name('stock.datatable');
            Route::get('{type}/create', [StockController::class, 'create'])->name('stock.create');
            Route::post('{type}', [StockController::class, 'store'])->name('stock.store');
            Route::get('{type}/{stock}/edit', [StockController::class, 'edit'])->name('stock.edit');
            Route::put('{type}/{stock}', [StockController::class, 'update'])->name('stock.update');
            Route::delete('{type}/{stock}', [StockController::class, 'delete'])->name('stock.delete');
            Route::get('{type}/{stock}', [StockController::class, 'show'])->name('stock.show');
        });

        // *Sales Invoices*
        Route::group(['prefix' => 'sales'], function () {
            Route::get('invoices/datatable', [SalesInvoiceController::class, 'datatable'])->name('sales.invoices.datatable');

            //Print
            Route::get('invoices/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('invoices.print');


            Route::resource('invoices', SalesInvoiceController::class);

            // *Sales Returns*
            Route::get('returns/datatable', [SalesReturnController::class, 'datatable'])->name('sales.returns.datatable');
            Route::get('returns/{return}/print', [SalesReturnController::class, 'print'])->name('sales.returns.print');
            Route::get('returns/invoice-products-options/{invoice}', [SalesReturnController::class, 'getInvoiceProductsOptions']);
            Route::get('returns/filter-invoices', [\App\Http\Controllers\Sales\SalesReturnController::class, 'filterInvoices']);


            Route::get('returns/invoice-product-options/{salesInvoiceId}/{productId}', [SalesReturnController::class, 'getReturnProductBatchOptions']);

            Route::resource('returns', SalesReturnController::class);
        });
    });
});

require __DIR__ . '/auth.php';
