<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::livewire('/booking', 'pages::booking.create')
    ->name('booking.create');

Route::livewire(
        '/dashboard/reservations',
        'pages::dashboard.reservations.index'
    )
        ->middleware(['auth', 'verified'])
        ->name('dashboard.reservations');
Route::livewire(
        '/dashboard/services',
        'pages::dashboard.services.index'
        )
        ->middleware(['auth', 'verified'])
        ->name('dashboard.services');
Route::livewire(
        '/dashboard/availabilities',
        'pages::dashboard.availabilities.index'
        )
        ->middleware(['auth', 'verified'])
        ->name('dashboard.availabilities');
Route::livewire(
        '/dashboard/resources',
        'pages::dashboard.resources.index'
        )
        ->middleware(['auth', 'verified'])
        ->name('dashboard.resources');
Route::livewire(
        '/dashboard/customization',
        'pages::dashboard.customization.index'
        )
        ->middleware(['auth', 'verified'])
        ->name('dashboard.customization');

