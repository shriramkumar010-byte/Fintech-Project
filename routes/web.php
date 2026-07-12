<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Support GET logout to avoid 419 when UI triggers a GET (graceful fallback).
Route::get('admin/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('filament.auth.login');
})->name('fallback.admin.logout');
