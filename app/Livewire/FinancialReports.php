<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Category;
use App\Models\Budget;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FinancialReports extends Component
{
    // Report filters
    public $reportPeriod = 'monthly'; // 'monthly', 'yearly', 'custom'
    public $selectedMonth;
    public $selectedYear;
    public $customStartDate;
    public $customEndDate;

    public $reportType = 'expense_summary'; // Default report type: 'expense_summary', 'income_expense_trend', 'budget_vs_actual', 'transaction_list'

    // Report data
    public $expenseCategoriesSummary = [];
    public $incomeExpenseTrend = [];
    public $budgetVsActualReport = [];
    public $transactionListReport = [];

    // Available years for filters (SQLite compatible)
    public $availableYears = [];

    /**
     * Mount lifecycle hook to set initial filter values and generate initial reports.
     */
    public function mount()
    {
        $this->selectedYear = now()->year;
        $this->selectedMonth = now()->month;
        $this->customStartDate = now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $this->customEndDate = now()->endOfMonth()->format('Y-m-d');

        $this->generateAvailableYears();
        $this->generateReports();
    }

    /**
     * Generate available years for filtering based on existing expense/income data.
     * Compatible with SQLite.
     */
    private function generateAvailableYears()
    {
        if (!Auth::check()) {
            $this->availableYears = collect();
            return;
        }

        $userId = Auth::id();

        $expenseYears = Expense::where('user_id', $userId)
                               ->get()
                               ->map(function ($expense) {
                                   return $expense->expense_date->year;
                               })
                               ->unique();

        $incomeYears = Income::where('user_id', $userId)
                              ->get()
                              ->map(function ($income) {
                                  return $income->income_date->year;
                              })
                              ->unique();

        $allYears = $expenseYears->merge($incomeYears)->unique()->sortDesc()->values();
        $this->availableYears = $allYears;
    }

    /**
     * Updated hook to regenerate reports when filters change.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['reportPeriod', 'selectedMonth', 'selectedYear', 'customStartDate', 'customEndDate', 'reportType'])) {
            $this->generateReports();
        }
    }

    /**
     * Main method to generate all selected reports based on filters.
     */
    public function generateReports()
    {
        if (!Auth::check()) {
            $this->resetReportData();
            session()->flash('error', 'Please log in to view reports.');
            return;
        }

        $this->resetReportData(); // Clear previous report data

        // Determine start and end dates based on selected period
        $startDate = null;
        $endDate = null;

        if ($this->reportPeriod === 'monthly') {
            $startDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->endOfMonth();
        } elseif ($this->reportPeriod === 'yearly') {
            $startDate = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($this->selectedYear, 12, 31)->endOfYear();
        } elseif ($this->reportPeriod === 'custom') {
            if ($this->customStartDate && $this->customEndDate) {
                $startDate = Carbon::parse($this->customStartDate)->startOfDay();
                $endDate = Carbon::parse($this->customEndDate)->endOfDay();
                if ($startDate->isAfter($endDate)) {
                    session()->flash('warning', 'Custom end date cannot be before start date.');
                    return;
                }
            } else {
                session()->flash('warning', 'Please select both custom start and end dates.');
                return;
            }
        } else {
            // Default to current month if no period or custom dates are selected
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
        }


        if ($this->reportType === 'expense_summary') {
            $this->getExpenseSummaryByCategory($startDate, $endDate);
        } elseif ($this->reportType === 'income_expense_trend') {
            $this->getIncomeExpenseTrend($this->selectedYear);
        } elseif ($this->reportType === 'budget_vs_actual') {
            $this->getBudgetVsActualReport($startDate, $endDate);
        } elseif ($this->reportType === 'transaction_list') {
            $this->getTransactionListReport($startDate, $endDate);
        }
    }

    /**
     * Resets all report data properties.
     */
    private function resetReportData()
    {
        $this->expenseCategoriesSummary = [];
        $this->incomeExpenseTrend = [];
        $this->budgetVsActualReport = [];
        $this->transactionListReport = [];
    }

    /**
     * Get expense summary by category for the selected period.
     */
    private function getExpenseSummaryByCategory(?Carbon $startDate, ?Carbon $endDate)
    {
        $userId = Auth::id();
        $query = Expense::where('user_id', $userId)->with('category');
        $totalExpensesAmount = 0;

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [$startDate, $endDate]);
        }

        $expenses = $query->get();

        $summarizedData = $expenses->groupBy('category.name')
            ->map(function ($groupedExpenses, $categoryName) use (&$totalExpensesAmount) {
                $sum = $groupedExpenses->sum('amount');
                $totalExpensesAmount += $sum;
                return [
                    'category' => $categoryName,
                    'amount' => $sum,
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();

        // Store the main data under a 'data' key
        $this->expenseCategoriesSummary['data'] = $summarizedData;
        $this->expenseCategoriesSummary['total_expenses'] = $totalExpensesAmount;
        $this->expenseCategoriesSummary['report_period_start'] = $startDate ? $startDate->format('Y-m-d') : null;
        $this->expenseCategoriesSummary['report_period_end'] = $endDate ? $endDate->format('Y-m-d') : null;
    }

    /**
     * Get income vs expense trend for the selected year.
     */
    private function getIncomeExpenseTrend(int $year)
    {
        $userId = Auth::id();

        // Initialize monthly data as a collection of arrays
        $monthlyData = collect();
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::createFromDate(null, $i, 1)->format('F');
            $monthlyData->put($i, [
                'month_name' => $monthName,
                'total_income' => 0,
                'total_expenses' => 0,
                'net_balance' => 0,
            ]);
        }

        // Fetch income for the year
        $incomeRecords = Income::where('user_id', $userId)
            ->whereYear('income_date', $year)
            ->get();

        foreach ($incomeRecords as $income) {
            $month = $income->income_date->month;
            // Retrieve, modify, and put back the array for the specific month
            $monthData = $monthlyData->get($month);
            $monthData['total_income'] += $income->amount;
            $monthlyData->put($month, $monthData);
        }

        // Fetch expenses for the year
        $expenseRecords = Expense::where('user_id', $userId)
            ->whereYear('expense_date', $year)
            ->get();

        foreach ($expenseRecords as $expense) {
            $month = $expense->expense_date->month;
            // Retrieve, modify, and put back the array for the specific month
            $monthData = $monthlyData->get($month);
            $monthData['total_expenses'] += $expense->amount;
            $monthlyData->put($month, $monthData);
        }

        // Calculate net balance for each month
        $this->incomeExpenseTrend['data'] = $monthlyData->map(function ($data) {
            $data['net_balance'] = $data['total_income'] - $data['total_expenses'];
            return $data;
        })->values()->all();

        $this->incomeExpenseTrend['report_year'] = $year;
    }

    /**
     * Get Budget vs. Actual report for the selected period.
     */
    private function getBudgetVsActualReport(?Carbon $startDate, ?Carbon $endDate)
    {
        $userId = Auth::id();
        $this->budgetVsActualReport['report_period_start'] = $startDate ? $startDate->format('Y-m-d') : null;
        $this->budgetVsActualReport['report_period_end'] = $endDate ? $endDate->format('Y-m-d') : null;
        $this->budgetVsActualReport['data'] = [];
        $this->budgetVsActualReport['total_budgeted'] = 0;
        $this->budgetVsActualReport['total_actual'] = 0;
        $this->budgetVsActualReport['total_difference'] = 0;

        if (!$startDate || !$endDate) {
            return;
        }

        // Fetch budgets for the period (active during any part of the period)
        $budgets = Budget::where('user_id', $userId)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
            })
            ->with('category')
            ->get();

        // Fetch expenses for the period
        $expenses = Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        // Initialize data structure with categories from budgets and all expense categories
        $reportData = collect();

        // Add budgeted categories
        foreach ($budgets as $budget) {
            $categoryName = $budget->category ? $budget->category->name : 'Overall Budget';
            // Use get and put to modify collection item correctly
            $item = $reportData->get($categoryName, [
                'category' => $categoryName,
                'budgeted' => 0,
                'actual' => 0,
                'difference' => 0,
                'status' => 'Under Budget',
            ]);
            $item['budgeted'] += $budget->amount;
            $reportData->put($categoryName, $item);
        }

        // Add actual expenses and match with categories
        foreach ($expenses as $expense) {
            $categoryName = $expense->category->name ?? 'Uncategorized';
            // Use get and put to modify collection item correctly
            $item = $reportData->get($categoryName, [
                'category' => $categoryName,
                'budgeted' => 0,
                'actual' => 0,
                'difference' => 0,
                'status' => 'Under Budget',
            ]);
            $item['actual'] += $expense->amount;
            $reportData->put($categoryName, $item);
        }

        // Calculate differences and status
        $reportData = $reportData->map(function ($item) {
            $item['difference'] = $item['budgeted'] - $item['actual'];
            if ($item['budgeted'] > 0) {
                if ($item['difference'] < 0) {
                    $item['status'] = 'Over Budget';
                } elseif ($item['difference'] === 0) {
                    $item['status'] = 'On Budget';
                } else {
                    $item['status'] = 'Under Budget';
                }
            } else {
                $item['status'] = $item['actual'] > 0 ? 'No Budget Set' : 'N/A';
            }
            return $item;
        })->sortBy('category')->values();

        $this->budgetVsActualReport['data'] = $reportData->toArray();

        // Calculate totals
        $this->budgetVsActualReport['total_budgeted'] = $reportData->sum('budgeted');
        $this->budgetVsActualReport['total_actual'] = $reportData->sum('actual');
        $this->budgetVsActualReport['total_difference'] = $this->budgetVsActualReport['total_budgeted'] - $this->budgetVsActualReport['total_actual'];
    }


    /**
     * Get Transaction List report for the selected period.
     */
    private function getTransactionListReport(?Carbon $startDate, ?Carbon $endDate)
    {
        $userId = Auth::id();
        $this->transactionListReport['report_period_start'] = $startDate ? $startDate->format('Y-m-d') : null;
        $this->transactionListReport['report_period_end'] = $endDate ? $endDate->format('Y-m-d') : null;
        $this->transactionListReport['data'] = [];
        $this->transactionListReport['total_income'] = 0;
        $this->transactionListReport['total_expenses'] = 0;
        $this->transactionListReport['net_balance'] = 0;

        if (!$startDate || !$endDate) {
            return;
        }

        // Fetch expenses
        $expenses = Expense::where('user_id', $userId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with('category', 'paymentMethod')
            ->get()
            ->map(function ($expense) {
                return [
                    'date' => $expense->expense_date,
                    'type' => 'expense',
                    'description' => $expense->description ?? ($expense->category->name ?? 'Expense'),
                    'category' => $expense->category->name ?? 'N/A',
                    'payment_method' => $expense->paymentMethod->name ?? 'N/A',
                    'amount' => $expense->amount,
                ];
            });

        // Fetch income
        $incomeRecords = Income::where('user_id', $userId)
            ->whereBetween('income_date', [$startDate, $endDate])
            ->get()
            ->map(function ($income) {
                return [
                    'date' => $income->income_date,
                    'type' => 'income',
                    'description' => $income->description ?? ($income->source ?? 'Income'),
                    'category' => 'N/A',
                    'payment_method' => 'N/A',
                    'amount' => $income->amount,
                ];
            });

        // Merge and sort all transactions by date
        $allTransactions = $expenses->concat($incomeRecords)->sortByDesc('date');

        $this->transactionListReport['data'] = $allTransactions->values()->toArray();

        // Calculate totals
        $this->transactionListReport['total_income'] = $allTransactions->where('type', 'income')->sum('amount');
        $this->transactionListReport['total_expenses'] = $allTransactions->where('type', 'expense')->sum('amount');
        $this->transactionListReport['net_balance'] = $this->transactionListReport['total_income'] - $this->transactionListReport['total_expenses'];
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        // Get months for filter dropdown
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
        }

        return view('livewire.financial-reports', [
            'months' => $months,
            'availableYears' => $this->availableYears,
        ]);
    }
}
