<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::search')->name('search');
Route::livewire('/inmueble/{id}', 'pages::listing')->name('listing');
