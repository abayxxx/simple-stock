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
use App\Http\Controllers\Sales\SalesReceiptController;
use App\Http\Controllers\Purchases\PurchasesInvoiceController;
use App\Http\Controllers\Purchases\PurchasesReturnController;
use App\Http\Controllers\Purchases\PurchasesPaymentController;
use App\Http\Controllers\Stocks\StockListController;
use App\Http\Controllers\Stocks\StockCardController;
use App\Http\Controllers\Sales\SalesPaymentController;

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

            // *Stock List*
            Route::get('lists', [StockListController::class, 'index'])->name('stocks.lists.index');
            Route::get('lists/datatable', [StockListController::class, 'datatable'])->name('stocks.lists.datatable');

            // *Stock Card*
            Route::get('cards', [StockCardController::class, 'index'])->name('stocks.cards');
            Route::get('cards/datatable', [StockCardController::class, 'datatable'])->name('stocks.cards.datatable');


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
        Route::group(['prefix' => 'sales', 'as' => 'sales.'], function () {
            Route::get('invoices/datatable', [SalesInvoiceController::class, 'datatable'])->name('invoices.datatable');

            //Print
            Route::get('invoices/{invoice}/print', [SalesInvoiceController::class, 'print'])->name('invoices.print');


            Route::resource('invoices', SalesInvoiceController::class);

            // *Sales Returns*
            Route::get('returns/datatable', [SalesReturnController::class, 'datatable'])->name('returns.datatable');
            Route::get('returns/{return}/print', [SalesReturnController::class, 'print'])->name('returns.print');
            Route::get('returns/invoice-products-options/{invoice}', [SalesReturnController::class, 'getInvoiceProductsOptions']);
            Route::get('returns/filter-invoices', [SalesReturnController::class, 'filterInvoices']);


            Route::get('returns/invoice-product-options/{salesInvoiceId}/{productId}', [SalesReturnController::class, 'getReturnProductBatchOptions']);
            Route::resource('returns', SalesReturnController::class);


            // *Sales Receipts*
            Route::get('receipts/datatable', [SalesReceiptController::class, 'datatable'])->name('receipts.datatable');
            Route::get('receipts/tarik-faktur-options', [SalesReceiptController::class, 'tarikFakturOptions'])->name('receipts.tarik_faktur_options');
            Route::get('receipts/{receipt}/print', [SalesReceiptController::class, 'print'])->name('receipts.print');
            Route::resource('receipts', SalesReceiptController::class);

            // *Payments*
            Route::get('payments/datatable', [SalesPaymentController::class, 'datatable'])->name('payments.datatable');
            Route::get('payments/tarik-nota-options', [SalesPaymentController::class, 'tarikNotaOptions'])->name('payments.tarik_nota_options');
            Route::get('payments/{payment}/print', [SalesPaymentController::class, 'print'])->name('payments.print');
            Route::resource('payments', SalesPaymentController::class);
        });

        // *Purchases*
        Route::group(['prefix' => 'purchases', 'as' => 'purchases.'], function () {
            Route::get('invoices/datatable', [PurchasesInvoiceController::class, 'datatable'])->name('invoices.datatable');
            Route::get('invoices/{invoice}/print', [PurchasesInvoiceController::class, 'print'])->name('invoices.print');
            Route::resource('invoices', PurchasesInvoiceController::class);

            // *Return
            Route::get('returns/datatable', [PurchasesReturnController::class, 'datatable'])->name('returns.datatable');
            Route::get('returns/{return}/print', [PurchasesReturnController::class, 'print'])->name('returns.print');
            Route::get('returns/invoice-products-options/{invoice}', [PurchasesReturnController::class, 'getInvoiceProductsOptions']);
            Route::get('returns/filter-invoices', [PurchasesReturnController::class, 'filterInvoices']);


            Route::get('returns/invoice-product-options/{purchasesInvoiceId}/{productId}', [PurchasesReturnController::class, 'getReturnProductBatchOptions']);

            Route::resource('returns', PurchasesReturnController::class);


            // *Payments*
            Route::get('payments/datatable', [PurchasesPaymentController::class, 'datatable'])->name('payments.datatable');
            Route::get('payments/tarik-nota-options', [PurchasesPaymentController::class, 'tarikNotaOptions'])->name('payments.tarik_nota_options');
            Route::get('payments/{payment}/print', [PurchasesPaymentController::class, 'print'])->name('payments.print');
            Route::resource('payments', PurchasesPaymentController::class);
        });
    });
});

require __DIR__ . '/auth.php';
