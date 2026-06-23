<?php

use App\Http\Controllers\CareerChangeController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/city/{slug}', [CityController::class, 'switch'])->name('city.switch');

Route::get('/professions', [ProfessionController::class, 'index'])->name('professions.index');
Route::get('/professions/{profession:slug}', [ProfessionController::class, 'show'])->name('professions.show');

Route::get('/test', [QuizController::class, 'show'])->name('quiz.show');
Route::post('/test', [QuizController::class, 'submit'])->name('quiz.submit');
Route::get('/test/result/{sessionId}', [QuizController::class, 'result'])->name('quiz.result');

Route::get('/career-change', [CareerChangeController::class, 'show'])->name('career-change.show');
Route::post('/career-change', [CareerChangeController::class, 'result'])->name('career-change.result');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/dashboard/profile', [DashboardController::class, 'updateProfile'])->name('dashboard.profile.update');
    Route::post('/dashboard/favorites/{profession}', [DashboardController::class, 'addFavorite'])->name('dashboard.favorites.store');
    Route::delete('/dashboard/favorites/{profession}', [DashboardController::class, 'removeFavorite'])->name('dashboard.favorites.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
