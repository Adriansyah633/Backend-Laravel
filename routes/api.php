<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PelangganController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StatusController;
use Illuminate\Foundation\Auth\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/




Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);
Route::get('/validate-token', [UserController::class, 'getAuthenticatedUser']);


Route::group(['middleware' => ['jwt.verify']], function() {
Route::get('/getUser', [UserController::class, 'getUser']);
Route::get('/pelanggan', [PelangganController::class, 'index']);
Route::post('/pelanggan-check', [PelangganController::class, 'getCheckPelanggan']);
Route::post('/bendel-check', [PelangganController::class, 'getCheckBendel']);
Route::post('/bendel', [PelangganController::class, 'cari_data_dism']);
Route::post('/cabang-check', [PelangganController::class, 'getCheckCabang']);
Route::get('/pelanggan/cari/{nolangg}',  [PelangganController::class, 'cari_data_nolangg']);
Route::post('/edit/{nolangg}',  [PelangganController::class, 'edit']);
Route::put('/simpan',  [PelangganController::class, 'simpan_data']);
Route::get('/pelanggan/cari/{dism}',  [PelangganController::class, 'cari_data_dism']);
Route::get('/getdetail',  [PelangganController::class, 'getdetailpelanggan']);
Route::get('/riwayat',  [PelangganController::class, 'riwayat']);
Route::delete('riwayat/{nolangg}', [PelangganController::class, 'delete']);
Route::get('/getStatus', [StatusController::class, 'index']);
Route::post('/upload-image/{nolangg}', [PelangganController::class, 'uploadImage']);

});



