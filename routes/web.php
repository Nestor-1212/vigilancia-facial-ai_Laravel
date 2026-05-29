<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));
Route::get('/login', fn() => view('pages.login'))->name('login');
Route::post('/logout', fn() => redirect('/login'))->name('logout');

// Páginas protegidas (auth verificada en el frontend con localStorage token)
Route::get('/dashboard',     fn() => view('pages.dashboard'));
Route::get('/personas',      fn() => view('pages.personas'));
Route::get('/camaras',       fn() => view('pages.camaras'));
Route::get('/alertas',       fn() => view('pages.alertas'));
Route::get('/live',          fn() => view('pages.live'));
Route::get('/reportes',          fn() => view('pages.reportes'));
Route::get('/reportes/celular',  fn() => view('pages.reportes-celular'));
Route::get('/configuracion',     fn() => view('pages.configuracion'));
