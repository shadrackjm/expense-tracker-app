<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringTransaction;
use App\Models\Budget; // To show active budgets
use Illuminate\Support\Facades\Auth;

class RecentActivity extends Component
{
    public $recentExpenses;
    public $recentIncome;
    public $upcomingRecurringTransactions;
    public $activeBudgets; // To display active budgets

    /**
     * Mount lifecycle hook to load initial data.
     */
    public function mount()
    {
        $this->loadRecentActivity();
    }

    /**
     * Load recent expenses, income, and upcoming recurring transactions.
     */
    public function loadRecentActivity()
    {
        if (Auth::check()) {
            $userId = Auth::id();

            // Fetch recent expenses (e.g., last 5)
            $this->recentExpenses = Expense::where('user_id', $userId)
                                            ->orderBy('expense_date', 'desc')
                                            ->orderBy('created_at', 'desc')
                                            ->take(5)
                                            ->with('category', 'paymentMethod') // Eager load relationships for display
                                            ->get();

            // Fetch recent income (e.g., last 5)
            $this->recentIncome = Income::where('user_id', $userId)
                                          ->orderBy('income_date', 'desc')
                                          ->orderBy('created_at', 'desc')
                                          ->take(5)
                                          ->get();

            // Fetch upcoming recurring transactions (e.g., next 5 due soon)
            $this->upcomingRecurringTransactions = RecurringTransaction::where('user_id', $userId)
                                                                        ->where('next_occurrence_date', '>=', now()->toDateString())
                                                                        ->orderBy('next_occurrence_date', 'asc')
                                                                        ->take(5)
                                                                        ->with('category') // Eager load category
                                                                        ->get();

            // Fetch active budgets
            $this->activeBudgets = Budget::where('user_id', $userId)
                                          ->where('start_date', '<=', now()->toDateString())
                                          ->where('end_date', '>=', now()->toDateString())
                                          ->with('category') // Eager load category
                                          ->orderBy('end_date', 'asc') // Show budgets ending soonest first
                                          ->get();
        } else {
            // Handle unauthenticated state if necessary (e.g., clear data)
            $this->recentExpenses = collect();
            $this->recentIncome = collect();
            $this->upcomingRecurringTransactions = collect();
            $this->activeBudgets = collect();
            error_log('User not authenticated for RecentActivity component.');
        }
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        return view('livewire.recent-activity');
    }
}
