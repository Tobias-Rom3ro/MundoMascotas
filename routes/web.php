<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        // Dashboard routes handled outside this group

        // Clientes
    Route::resource('clients', ClientController::class);
    Route::get('clients/{client}/history', [ClientController::class, 'history'])->name('clients.history');
    Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');

        // Mascotas
    Route::resource('pets', PetController::class);
    Route::get('pets/{pet}/medical-history', [PetController::class, 'medicalHistory'])->name('pets.medical-history');
    Route::get('pets/search', [PetController::class, 'search'])->name('pets.search');

        // Servicios
    Route::resource('services', ServiceController::class);
    Route::patch('services/{service}/price', [ServiceController::class, 'updatePrice'])->name('services.update-price');

        // Citas
    Route::resource('appointments', AppointmentController::class);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.update-status');
    Route::get('appointments/calendar', [AppointmentController::class, 'calendar'])->name('appointments.calendar');
    });

// Rutas públicas
    Route::get('services/catalog', [ServiceController::class, 'publicCatalog'])->name('services.public-catalog');

require __DIR__.'/auth.php';
