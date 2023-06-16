<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
dd('app deployed');
});

Route::get('/test-api-call',[\App\Http\Controllers\HubApiController::class,'processApi']);
Route::get('/register-webhook',[\App\Http\Controllers\HubApiController::class,'registerWebhooks']);
Route::any('/subscription-webhook-created',[\App\Http\Controllers\HubApiController::class,'subscriptionCreated']);
Route::any('/subscription-webhook-updated',[\App\Http\Controllers\HubApiController::class,'subscriptionUpdated']);
Route::any('/subscription-webhook-cancelled',[\App\Http\Controllers\HubApiController::class,'subscriptionCancelled']);
Route::any('/seal-topic-subscription-created',[\App\Http\Controllers\HubApiController::class,'sealTopicSubscriptionCreated']);


