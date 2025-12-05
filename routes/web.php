<?php

use App\Http\Controllers\Settings;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Store Setup & Settings
    Route::get('store/setup', [App\Http\Controllers\StoreController::class, 'setupWizard'])->name('store.setup-wizard');
    Route::post('store/setup/step', [App\Http\Controllers\StoreController::class, 'completeSetupStep'])->name('store.setup-step');
    Route::get('store/settings', [App\Http\Controllers\StoreController::class, 'settings'])->name('store.settings');
    Route::put('store/settings/basic', [App\Http\Controllers\StoreController::class, 'updateBasic'])->name('store.settings.basic');
    Route::put('store/settings/bank', [App\Http\Controllers\StoreController::class, 'updateBank'])->name('store.settings.bank');
    Route::put('store/settings/bricklink', [App\Http\Controllers\StoreController::class, 'updateBrickLink'])->name('store.settings.bricklink');
    Route::put('store/settings/smtp', [App\Http\Controllers\StoreController::class, 'updateSmtp'])->name('store.settings.smtp');
    Route::put('store/settings/nextcloud', [App\Http\Controllers\StoreController::class, 'updateNextcloud'])->name('store.settings.nextcloud');
    Route::post('store/settings/smtp/test', [App\Http\Controllers\StoreController::class, 'testSmtp'])->name('store.settings.smtp.test');
    Route::post('store/settings/nextcloud/test', [App\Http\Controllers\StoreController::class, 'testNextcloud'])->name('store.settings.nextcloud.test');

    // Settings
    Route::get('settings/profile', [Settings\ProfileController::class, 'edit'])->name('settings.profile.edit');
    Route::put('settings/profile', [Settings\ProfileController::class, 'update'])->name('settings.profile.update');
    Route::delete('settings/profile', [Settings\ProfileController::class, 'destroy'])->name('settings.profile.destroy');
    Route::get('settings/password', [Settings\PasswordController::class, 'edit'])->name('settings.password.edit');
    Route::put('settings/password', [Settings\PasswordController::class, 'update'])->name('settings.password.update');
    Route::get('settings/appearance', [Settings\AppearanceController::class, 'edit'])->name('settings.appearance.edit');
});

Route::middleware(['auth', 'store.setup'])->group(function () {
    // Orders
    Route::resource('orders', App\Http\Controllers\OrderController::class)->only(['index', 'show']);
    Route::get('orders/{order}/pack', [App\Http\Controllers\OrderController::class, 'pack'])->name('orders.pack');
    Route::post('orders/{order}/pack-item', [App\Http\Controllers\OrderController::class, 'packItem'])->name('orders.pack-item');
    Route::post('orders/{order}/unpack-item', [App\Http\Controllers\OrderController::class, 'unpackItem'])->name('orders.unpack-item');
    Route::post('orders/{order}/sync', [App\Http\Controllers\OrderController::class, 'sync'])->name('orders.sync');
    Route::post('orders/sync-all', [App\Http\Controllers\OrderController::class, 'syncAll'])->name('orders.sync-all');
    Route::post('orders/{order}/status', [App\Http\Controllers\OrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('orders/{order}/shipping', [App\Http\Controllers\OrderController::class, 'updateShipping'])->name('orders.update-shipping');
    Route::post('orders/{order}/ship', [App\Http\Controllers\OrderController::class, 'ship'])->name('orders.ship');

    // Inventory
    Route::resource('inventory', App\Http\Controllers\InventoryController::class);
    Route::post('inventory/sync', [App\Http\Controllers\InventoryController::class, 'sync'])->name('inventory.sync');

    // Feedback
    Route::post('orders/{order}/feedback/sync', [App\Http\Controllers\FeedbackController::class, 'sync'])->name('orders.feedback.sync');
    Route::post('orders/{order}/feedback', [App\Http\Controllers\FeedbackController::class, 'store'])->name('orders.feedback.store');

    // Invoices
    Route::resource('invoices', App\Http\Controllers\InvoiceController::class)->only(['index', 'show']);
    Route::post('orders/{order}/invoice', [App\Http\Controllers\InvoiceController::class, 'createFromOrder'])->name('orders.create-invoice');
    Route::get('invoices/{invoice}/pdf', [App\Http\Controllers\InvoiceController::class, 'downloadPDF'])->name('invoices.download-pdf');
    Route::get('invoices/{invoice}/stream', [App\Http\Controllers\InvoiceController::class, 'streamPDF'])->name('invoices.stream-pdf');
    Route::post('invoices/{invoice}/send', [App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
    Route::post('invoices/{invoice}/mark-paid', [App\Http\Controllers\InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::put('invoices/{invoice}', [App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::post('invoices/{invoice}/reupload-nextcloud', [App\Http\Controllers\InvoiceController::class, 'reuploadToNextcloud'])->name('invoices.reupload-nextcloud');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\Admin\AdminController::class, 'index'])->name('dashboard');
    Route::get('activity-logs', [App\Http\Controllers\Admin\AdminController::class, 'activityLogs'])->name('activity-logs');
    Route::post('activity-logs/clear', [App\Http\Controllers\Admin\AdminController::class, 'clearOldLogs'])->name('activity-logs.clear');
});

require __DIR__.'/auth.php';
