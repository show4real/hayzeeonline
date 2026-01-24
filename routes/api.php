<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\YoutubeController;
use App\Http\Controllers\Api\ReferrerController;
use App\Http\Controllers\Api\PriceEditController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\InsuranceApplicationController;
use App\Http\Controllers\Api\ClientConsentController;
use MeiliSearch\Client;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::post('/initiate-payment', [PaymentController::class, 'initiatePayment']);
Route::post('/handle-payment-callback', [PaymentController::class, 'handlePaymentCallback']);
Route::post('complete/order', [PaymentController::class, 'completeOrder']);

Route::controller(InsuranceApplicationController::class)->group(function () {
    Route::get('insurance-applications', 'index');
    Route::post('insurance-applications', 'store');
});

Route::get('client-consents', [ClientConsentController::class, 'index']);
Route::post('client-consents', [ClientConsentController::class, 'store']);

Route::get('/configure-meilisearch', function () {
    $client = new Client('http://195.35.48.107:7700', null); 
    $index = $client->index('product_index');

    //$index->updateSortableAttributes(['availability']);
    $index->updateFilterableAttributes(['availability']);

    return 'MeiliSearch sortable attributes configured.';
});


Route::controller(ReferrerController::class)->group(function () {
    Route::post('referrer/codes', 'referrerCode');
});



Route::controller(AuthController::class)->group(function () {
    Route::post('signup', 'signup');
    Route::post('login', 'login');
    Route::post('login/referrer', 'loginReferrer');
    Route::post('verify', 'verify');
});

Route::controller(CategoryController::class)->group(function () {
    Route::post('user/categories', 'userCategories');
    Route::post('user/allcategories', 'index');
    Route::post('user/allcats', 'allCats');
    Route::get('show/category/{slug}', 'show');
});

Route::controller(BrandController::class)->group(function () {

    Route::post('user/allbrands', 'index');
    Route::get('show/brand/{id}', 'show');
});

Route::controller(OrderController::class)->group(function () {
    Route::post('store/order', 'addOrder');
});


Route::controller(ProductController::class)->group(function () {

    Route::post('user/product/images/{id}', 'productImages');
    Route::post('related/products/{id}', 'relatedProducts');
    Route::post('product/infos/{id}', 'productDescriptions');
});

Route::controller(ShopController::class)->group(function () {
    Route::post('products/trending', 'trending');
    Route::post('user/products', 'products');
    Route::post('search/products', 'searchProducts');
    Route::post('searchall/products', 'searchAllProducts');
    Route::post('user/brands', 'brands');
    Route::post('other_sales', 'otherSales');
    Route::post('quick_search', 'quickProductSearch');
    Route::post('laptop_products', 'laptopProducts');

    Route::post('category/products', 'categoryProducts');
    Route::post('shop/products', 'shopProducts');
    Route::get('singleproduct/{slug}', 'show');
});

Route::controller(BlogController::class)->group(function () {
    Route::post('user/allblogs', 'index');
    Route::get('blog/{product}', 'show');
});

Route::middleware(['auth:api', 'CheckReferrer'])->group(function () {

    Route::controller(ReferrerController::class)->group(function () {
        Route::post('referrer/update/profile', 'addProfile');
        Route::post('referrer/profile', 'referrerProfile');
        Route::post('referrer/transactions', 'myTransactions');
        Route::post('referrer/dashboard', 'referrerDashboard');

    });

});






Route::middleware(['auth:api', 'CheckAdmin'])->group(function () {

    Route::controller(UserController::class)->group(function () {
        Route::post('users', 'index');
        Route::post('delete/user/{id}', 'delete');
    });

     Route::controller(PriceEditController::class)->group(function () {

       Route::post('update/price', 'create');
       Route::post('prices', 'index');
       Route::post('delete/price/{id}', 'delete');
    });


    Route::controller(ReferrerController::class)->group(function () {
        Route::post('referrers', 'referrers');
        Route::post('allreferrers', 'allReferrers');

        Route::post('referrer/approve', 'approve');
        Route::post('delete/referrer/{id}', 'deleteReferrer');

        Route::post('transactions', 'allTransactions');
        Route::post('add/transactions', 'addTransaction');
        Route::post('update/transaction/{id}', 'updateTransaction');
        Route::post('delete/transaction/{id}', 'deleteTransaction');

        

        
    });

    Route::controller(BrandController::class)->group(function () {
        Route::post('brands', 'index');
        Route::post('store/brand', 'create');
        Route::post('update/brand/{brand}', 'update');
        Route::post('delete/brand/{id}', 'delete');
        
    });

    Route::controller(CategoryController::class)->group(function () {
        Route::post('categories', 'index');
        Route::post('store/category', 'create');
        Route::post('update/category/{category}', 'update');
        Route::post('delete/category/{id}', 'delete');
        
    });

    Route::controller(BlogController::class)->group(function () {
        Route::post('blogs', 'index');
        Route::post('store/blog', 'store');
        Route::post('update/blog/{blog}', 'update');
        Route::post('delete/blog/{id}', 'delete');
    });

    Route::controller(ProductController::class)->group(function () {
        Route::post('products', 'index');
        Route::post('product/descriptions/{id}', 'productDescriptions');
        Route::post('product/images/{id}', 'productImages');
        Route::post('store/product', 'store');
        Route::post('update/product/{product}', 'update');
        Route::post('delete/product/{id}', 'delete');
        Route::get('product/images/{id}', 'productimages');
    });

    Route::controller(OrderController::class)->group(function () {
        Route::post('orders', 'index');
        // Route::post('store/order', 'store');
        Route::post('update/order/{order}', 'update');
        Route::post('delete/order/{id}', 'delete');
    });

    Route::controller(YoutubeController::class)->group(function () {
        Route::post('youtubes', 'index');
        Route::post('store/youtube', 'addYoutube');
    });

    Route::controller(NoticeController::class)->group(function () {
        Route::post('notice', 'index');
        Route::post('store/notice', 'create');
    });
});
