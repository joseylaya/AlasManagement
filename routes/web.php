<?php

use App\Livewire\ActivityLogs\Index as ActivityLogsIndex;

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Inventory\Index as InventoryIndex;
use App\Livewire\Orders\Create as OrdersCreate;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrdersShow;
use App\Livewire\Products\Create as ProductsCreate;
use App\Livewire\Products\Edit as ProductsEdit;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use App\Livewire\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardIndex::class)->name('dashboard');
    Route::get('/dashboard', DashboardIndex::class);

    // Products Catalog
    Route::get('/products', ProductsIndex::class)->name('products.index');
    Route::get('/products/create', ProductsCreate::class)->name('products.create');
    Route::get('/products/{product}/edit', ProductsEdit::class)->name('products.edit');

    // Inventory Control
    Route::get('/inventory', InventoryIndex::class)->name('inventory.index');

    // Orders & Sales
    Route::get('/orders', OrdersIndex::class)->name('orders.index');
    Route::get('/orders/create', OrdersCreate::class)->name('orders.create');
    Route::get('/orders/{id}', OrdersShow::class)->name('orders.show');

    // Finance & Cash Flow
    Route::get('/finance', FinanceIndex::class)->name('finance.index');

    // Reports
    Route::get('/reports', ReportsIndex::class)->name('reports.index');

    // Activity Logs
    Route::get('/activity-logs', ActivityLogsIndex::class)->name('activity-logs.index');

    // Users Management
    Route::get('/users', UsersIndex::class)->name('users.index');

    // Settings
    Route::get('/settings', SettingsIndex::class)->name('settings.index');
});
