<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class StatsOverview extends Component
{
    // Public properties to hold the fetched data
    public $totalExpenses = 0;
    public $totalIncome = 0;
    public $netBalance = 0;
    public $totalRecurringTransactions = 0;

    /**
     * Mount lifecycle hook to initialize data.
     * This runs once when the component is first mounted.
     */
    public function mount()
    {
        $this->loadStats();
    }

    /**
     * Load the statistical data for the authenticated user.
     */
    public function loadStats()
    {
        // Ensure a user is authenticated before fetching data
        if (Auth::check()) {
            $userId = Auth::id();
            $currentMonthStart = now()->startOfMonth();
            $currentMonthEnd = now()->endOfMonth();

            // Calculate total expenses for the current month
            $this->totalExpenses = Expense::where('user_id', $userId)
                                          ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
                                          ->sum('amount');

           

            // Calculate total income for the current month
            $this->totalIncome = Income::where('user_id', $userId)
                                       ->whereBetween('income_date', [$currentMonthStart, $currentMonthEnd])
                                       ->sum('amount');
            

            // Calculate net balance
            $this->netBalance = $this->totalIncome - $this->totalExpenses;
            $this->netBalance = Number::abbreviate($this->netBalance) ?: 0; // Ensure it's at least 0

            // Count total active recurring transactions
            $this->totalRecurringTransactions = RecurringTransaction::where('user_id', $userId)
                                                                    ->where(function ($query) {
                                                                        $query->whereNull('end_date')
                                                                              ->orWhere('end_date', '>=', now()->toDateString());
                                                                    })
                                                                    ->count();
        } else {
            // Handle case where user is not authenticated (e.g., redirect or show login prompt)
            // For now, we'll just keep values at 0
            // You might want to implement a flash message or redirect here in a real app.
            error_log('User not authenticated for StatsOverview component.');
        }
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        return view('livewire.stats-overview');
    }
}
