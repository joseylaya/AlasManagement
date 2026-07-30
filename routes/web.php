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
use App\Http\Controllers\OfflineSyncController;

// ─── Guest Routes ────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// ─── Logout ──────────────────────────────────────────────────────
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// ─── Authenticated Routes ─────────────────────────────────────────
Route::middleware('auth')->group(function () {

    Route::post('/sync/orders', [OfflineSyncController::class, 'order'])->name('sync.orders');
    Route::post('/sync/owner-withdrawals', [OfflineSyncController::class, 'ownerWithdrawal'])->name('sync.owner-withdrawals');

    // Dashboard (all roles)
    Route::get('/', DashboardIndex::class)->name('dashboard');
    Route::get('/dashboard', DashboardIndex::class);

    // ─── Products — All roles can VIEW; Manager/Owner can CREATE/EDIT
    Route::get('/products', ProductsIndex::class)->name('products.index');
    Route::get('/products/create', ProductsCreate::class)
        ->middleware('role:owner,manager')
        ->name('products.create');
    Route::get('/products/{product}/edit', ProductsEdit::class)
        ->middleware('role:owner,manager')
        ->name('products.edit');

    // ─── Inventory — All roles can VIEW
    Route::get('/inventory', InventoryIndex::class)->name('inventory.index');

    // ─── Orders — All roles can view and create; approval handled in Livewire
    Route::get('/orders', OrdersIndex::class)->name('orders.index');
    Route::get('/orders/create', OrdersCreate::class)->name('orders.create');
    Route::get('/orders/{id}', OrdersShow::class)->name('orders.show');

    // ─── Finance — all roles may view the permitted, read-only ledger
    Route::get('/finance', FinanceIndex::class)
        ->name('finance.index');

    // ─── Reports / Analytics — Owner + Manager only
    Route::get('/reports', ReportsIndex::class)
        ->middleware('role:owner,manager')
        ->name('reports.index');

    // ─── Activity Logs — All roles (read-only for Staff)
    Route::get('/activity-logs', ActivityLogsIndex::class)->name('activity-logs.index');

    // ─── Users Management — Owner only
    Route::get('/users', UsersIndex::class)
        ->middleware('role:owner')
        ->name('users.index');

    // ─── Settings — Owner only
    Route::get('/settings', SettingsIndex::class)
        ->middleware('role:owner')
        ->name('settings.index');
});
