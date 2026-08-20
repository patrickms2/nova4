<?php

use App\NativeComponents\StaffAccess\DashboardScreen;
use App\NativeComponents\StaffAccess\LoginScreen;
use App\NativeComponents\StaffAccess\ReportScreen;
use Illuminate\Support\Facades\Route;

Route::native('/staff/login', LoginScreen::class)->name('mobile.staff.login');
Route::native('/staff/dashboard', DashboardScreen::class)->name('mobile.staff.dashboard');
Route::native('/staff/report/{sessionId}', ReportScreen::class)->name('mobile.staff.report');
