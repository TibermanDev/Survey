<?php

use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\SurveiController;
use Illuminate\Support\Facades\Route;

// Karyawan (tanpa login)
Route::get('/', [SurveiController::class, 'index'])->name('survei.index');
Route::post('/mulai', [SurveiController::class, 'start'])->name('survei.start');
Route::get('/isi', [SurveiController::class, 'form'])->name('survei.form');
Route::post('/isi', [SurveiController::class, 'submit'])->name('survei.submit');
Route::get('/selesai', [SurveiController::class, 'done'])->name('survei.done');

// Panitia: hanya buka/tutup hari survei, tidak bisa melihat jawaban
Route::prefix('panitia')->name('panitia.')->group(function () {
    Route::get('/', [PanitiaController::class, 'index'])->name('index');
    Route::post('/masuk', [PanitiaController::class, 'login'])->name('login')->middleware('throttle:kunci');
    Route::post('/hari/{day}', [PanitiaController::class, 'toggle'])->name('toggle')->whereNumber('day');
    Route::post('/keluar', [PanitiaController::class, 'logout'])->name('logout');
});

// Laporan: hanya Pak Andy (dengan kunci pribadi)
Route::prefix('laporan')->name('laporan.')->middleware('cache.headers:no_store;private')->group(function () {
    Route::get('/', [LaporanController::class, 'index'])->name('index');
    Route::post('/buat', [LaporanController::class, 'setup'])->name('setup')->middleware('throttle:kunci');
    Route::post('/buka', [LaporanController::class, 'unlock'])->name('unlock')->middleware('throttle:kunci');
    Route::get('/lupa', [LaporanController::class, 'forgotForm'])->name('forgot');
    Route::post('/lupa', [LaporanController::class, 'forgot'])->name('forgot.submit')->middleware('throttle:kunci');
    Route::get('/ringkasan', [LaporanController::class, 'summary'])->name('summary');
    Route::get('/karyawan', [LaporanController::class, 'people'])->name('people');
    Route::get('/karyawan/{code}', [LaporanController::class, 'person'])->name('person');
    Route::get('/ganti-kunci', [LaporanController::class, 'changeKeyForm'])->name('change-key');
    Route::post('/ganti-kunci', [LaporanController::class, 'changeKey'])->name('change-key.submit');
    Route::post('/kunci', [LaporanController::class, 'lock'])->name('lock');
});
