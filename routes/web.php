<?php

use App\Models\Budget;
use App\Models\Category;
use App\Livewire\ManageBudget;
use App\Livewire\ManageIncomes;
use App\Livewire\ManageCategory;
use App\Livewire\ManageExpense;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Appearance;
use Illuminate\Support\Facades\Route;
use App\Livewire\ManagePaymentMethods;
use App\Livewire\ManageRecurringTransactions;
use App\Livewire\SettingsPage;
use Illuminate\Database\Capsule\Manager;

Route::redirect('/', '/login')->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Route::get('/categories', ManageCategory::class)->name('categories.manage');
    Route::get('/budget', ManageBudget::class)->name('budget.manage');
    Route::get('/income', ManageIncomes::class)->name('income.manage');
    Route::get('/payment-methods', ManagePaymentMethods::class)->name('payment-methods.manage');
    Route::get('/expenses', ManageExpense::class)->name('expenses.manage');
    Route::get('/reccuring-transactions', ManageRecurringTransactions::class)->name('recurring-transactions.manage');
    Route::get('/settings-page', SettingsPage::class)->name('settings.page');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
