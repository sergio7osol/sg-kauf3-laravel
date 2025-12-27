<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Links;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\ShopAddressController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseReceiptFileController;
use App\Http\Controllers\Api\UserPaymentMethodController;
use App\Http\Controllers\Api\ReceiptController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('shops')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ShopController::class, 'index']);
    Route::get('/{shop}', [ShopController::class, 'show']);
    Route::post('/', [ShopController::class, 'store']);

    // Shop Addresses (nested under shops)
    Route::prefix('/{shop}/addresses')->group(function () {
        Route::get('/', [ShopAddressController::class, 'index']);
        Route::post('/', [ShopAddressController::class, 'store']);
        Route::get('/{address}', [ShopAddressController::class, 'show']);
        Route::put('/{address}', [ShopAddressController::class, 'update']);
        Route::patch('/{address}', [ShopAddressController::class, 'update']);
        Route::patch('/{address}/set-primary', [ShopAddressController::class, 'setPrimary']);
        Route::patch('/{address}/toggle-active', [ShopAddressController::class, 'toggleActive']);
    });
});

Route::prefix('purchases')->middleware('auth:sanctum')->group(function () {
    Route::get('/date-range', [PurchaseController::class, 'dateRange']);
    Route::get('/', [PurchaseController::class, 'index']);
    Route::post('/', [PurchaseController::class, 'store']);
    Route::get('/{purchase}', [PurchaseController::class, 'show']);
    Route::put('/{purchase}', [PurchaseController::class, 'update']);
    Route::patch('/{purchase}', [PurchaseController::class, 'update']);
    Route::delete('/{purchase}', [PurchaseController::class, 'destroy']);

    // Purchase receipt attachments
    Route::get('/{purchase}/attachments/{attachment}/download', [PurchaseReceiptFileController::class, 'download']);
    Route::delete('/{purchase}/attachments/{attachment}', [PurchaseReceiptFileController::class, 'destroy']);
});

Route::prefix('user-payment-methods')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserPaymentMethodController::class, 'index']);
    Route::post('/', [UserPaymentMethodController::class, 'store']);
    Route::get('/{user_payment_method}', [UserPaymentMethodController::class, 'show']);
    Route::put('/{user_payment_method}', [UserPaymentMethodController::class, 'update']);
    Route::patch('/{user_payment_method}', [UserPaymentMethodController::class, 'update']);
    Route::delete('/{user_payment_method}', [UserPaymentMethodController::class, 'destroy']);
});

Route::prefix('receipts')->middleware('auth:sanctum')->group(function () {
    Route::post('/parse', [ReceiptController::class, 'parse']);
    Route::get('/supported-shops', [ReceiptController::class, 'supportedShops']);
});

Route::middleware('auth:sanctum')->resource('/links', Links::class );

Route::get("/profiles/{id}", function ($id) {
    $user = User::find($id);
    $links = $user->links()->select('short_link')->get();
    return response()->json([
        "user"=> $user,
        "links"=> $links
    ]);
});

Route::get('/health', fn () => response()->json(['status' => 'ok']));
