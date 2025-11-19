<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Links;
use App\Http\Controllers\Api\ShopController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('shops')->group(function () {
    Route::get('/', [ShopController::class, 'index']);
    Route::get('/{shop}', [ShopController::class, 'show']);
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->resource('/links', Links::class );

Route::get("/profiles/{id}", function ($id) {
    $user = User::find($id);
    $links = $user->links()->select('short_link')->get();
    return response()->json([
        "user"=> $user,
        "links"=> $links
    ]);
});
