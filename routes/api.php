<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Links;

Route::get('/user', function (Request $request) {
    return $request->user();
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
