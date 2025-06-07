<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Income;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Carbon\Carbon;

class ManageIncomes extends Component
{
    use WithPagination; // Enable pagination for income records

    // Properties for income form
    public $amount = 0.00;
    public $source = '';
    public $description = '';
    public $income_date;
    public $editingIncomeId = null; // Stores the ID of the income record being edited

    // Properties for modal state
    public $showIncomeModal = false;

    // Properties for search and filter
    public $search = '';
    public $filterMonth = ''; // Filter by month
    public $filterYear = ''; // Filter by year

    protected $paginationTheme = 'tailwind'; // Use Tailwind for pagination styling

    /**
     * Mount lifecycle hook to set initial date values.
     */
    public function mount()
    {
        $this->income_date = now()->format('Y-m-d');
        $this->filterYear = now()->year;
        $this->filterMonth = now()->month;
    }

    /**
     * Validation rules for the income form.
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'source' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'income_date' => ['required', 'date'],
        ];
    }

    /**
     * Listen for changes to search or filter properties to reset pagination.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterMonth', 'filterYear'])) {
            $this->resetPage(); // Reset pagination when search or filter changes
        }
    }

    /**
     * Reset form fields and validation errors.
     */
    public function resetForm()
    {
        $this->amount = 0.00;
        $this->source = '';
        $this->description = '';
        $this->income_date = now()->format('Y-m-d');
        $this->editingIncomeId = null;
        $this->resetValidation(); // Clear validation errors
    }

    /**
     * Open the modal for creating a new income record.
     */
    public function createIncome()
    {
        $this->resetForm(); // Clear any previous form data
        $this->showIncomeModal = true;
    }

    /**
     * Store a new income record in the database.
     */
    public function storeIncome()
    {
        $this->validate(); // Run validation

        Income::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'source' => $this->source,
            'description' => $this->description,
            'income_date' => $this->income_date,
            // 'start_date' and 'end_date' are not used in this context, so they are omitted
            // If you need them, you can add them here
            'start_date' => now()->startOfMonth()->format('Y-m-d'), // Default to start of current month
            'end_date' => now()->endOfMonth()->format('Y-m-d'), // Default to end of current month
        ]);

        session()->flash('success', 'Income added successfully!');
        $this->showIncomeModal = false; // Close modal
        $this->resetForm(); // Reset form for next entry
    }

    /**
     * Open the modal and populate form for editing an existing income record.
     *
     * @param int $incomeId
     */
    public function editIncome(int $incomeId)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($incomeId);

        $this->editingIncomeId = $income->id;
        $this->amount = $income->amount;
        $this->source = $income->source;
        $this->description = $income->description;
        $this->income_date = $income->income_date->format('Y-m-d'); // Format date for input field
        $this->showIncomeModal = true;
    }

    /**
     * Update an existing income record in the database.
     */
    public function updateIncome()
    {
        $this->validate(); // Run validation

        if ($this->editingIncomeId) {
            $income = Income::where('user_id', Auth::id())->findOrFail($this->editingIncomeId);
            $income->update([
                'amount' => $this->amount,
                'source' => $this->source,
                'description' => $this->description,
                'income_date' => $this->income_date,
            ]);

            session()->flash('success', 'Income updated successfully!');
            $this->showIncomeModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete an income record from the database.
     *
     * @param int $incomeId
     */
    public function deleteIncome(int $incomeId)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($incomeId);
        $income->delete();
        session()->flash('success', 'Income deleted successfully!');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = Income::where('user_id', Auth::id()); // Always filter by authenticated user

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('source', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('amount', 'like', '%' . $this->search . '%'); // Search by amount as well
            });
        }

        // Apply month and year filters
        if ($this->filterYear) {
            $query->whereYear('income_date', $this->filterYear);
        }
        if ($this->filterMonth) {
            $query->whereMonth('income_date', $this->filterMonth);
        }

        $incomes = $query->orderBy('income_date', 'desc')->paginate(10); // Paginate results

        // Get years with income data for filter dropdown (SQLite compatible)
        $availableYears = Income::where('user_id', Auth::id())
                                ->get() // Fetch all income records for the user
                                ->map(function ($income) {
                                    return $income->income_date->year; // Extract year using Carbon
                                })
                                ->unique() // Get unique years
                                ->sortDesc(); // Sort in descending order

        // Get months for filter dropdown
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
        }

        return view('livewire.manage-incomes', [
            'incomes' => $incomes,
            'availableYears' => $availableYears,
            'months' => $months,
        ]);
    }
}
