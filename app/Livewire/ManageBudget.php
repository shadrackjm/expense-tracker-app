<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Budget;
use App\Models\Category; // Needed to populate category dropdown
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Carbon\Carbon; // For date manipulation
use Masmerise\Toaster\Toaster;

class ManageBudget extends Component
{
    use WithPagination; // Enable pagination for budgets

    // Properties for budget form
    public $amount = 0.00;
    public $category_id = ''; // Foreign key for Category
    public $start_date;
    public $end_date;
    public $editingBudgetId = null; // Stores the ID of the budget being edited

    // Properties for modal state
    public $showBudgetModal = false;

    // Properties for search and filter
    public $search = '';
    public $filterCategoryId = ''; // Filter by category
    public $filterStatus = 'active'; // 'active', 'past', 'future', 'all'

    protected $paginationTheme = 'tailwind'; // Use Tailwind for pagination styling

    /**
     * Mount lifecycle hook to set initial date values.
     */
    public function mount()
    {
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    /**
     * Validation rules for the budget form.
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category_id' => [
                'nullable', // Budgets can be overall (null category_id)
                'exists:categories,id',
                // Custom rule to ensure category belongs to the user if not null
                function ($attribute, $value, $fail) {
                    if ($value && Auth::check() && !Category::where('id', $value)->where('user_id', Auth::id())->exists()) {
                        $fail('The selected category is invalid or does not belong to you.');
                    }
                },
            ],
            'start_date' => ['required', 'date'],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                // Custom rule to prevent overlapping budgets for the same user/category
                function ($attribute, $value, $fail) {
                    if (!Auth::check()) {
                        return; // Skip if no user is authenticated
                    }

                    $query = Budget::where('user_id', Auth::id())
                                   ->where('category_id', $this->category_id); // Check for the specific category

                    // Exclude the current budget being edited
                    if ($this->editingBudgetId) {
                        $query->where('id', '!=', $this->editingBudgetId);
                    }

                    // Check for overlapping periods
                    // A budget overlaps if:
                    // (start_date <= new_end_date AND end_date >= new_start_date)
                    $overlaps = $query->where(function ($q) {
                        $q->where('start_date', '<=', $this->end_date)
                          ->where('end_date', '>=', $this->start_date);
                    })->exists();

                    if ($overlaps) {
                        $fail('An overlapping budget already exists for this category during the selected period.');
                    }
                },
            ],
        ];
    }

    /**
     * Listen for changes to search or filter properties to reset pagination.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterCategoryId', 'filterStatus'])) {
            $this->resetPage(); // Reset pagination when search or filter changes
        }
    }

    /**
     * Reset form fields and validation errors.
     */
    public function resetForm()
    {
        $this->amount = 0.00;
        $this->category_id = '';
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
        $this->editingBudgetId = null;
        $this->resetValidation();
    }

    /**
     * Open the modal for creating a new budget.
     */
    public function createBudget()
    {
        $this->resetForm();
        $this->showBudgetModal = true;
    }

    /**
     * Store a new budget in the database.
     */
    public function storeBudget()
    {
        $this->validate();

        Budget::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'category_id' => $this->category_id ?: null, // Store null if category_id is empty string
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);

        // session()->flash('success', 'Budget added successfully!');
        Toaster::success('Budget added successfully!'); 
        $this->showBudgetModal = false;
        $this->resetForm();
    }

    /**
     * Open the modal and populate form for editing an existing budget.
     *
     * @param int $budgetId
     */
    public function editBudget(int $budgetId)
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);

        $this->editingBudgetId = $budget->id;
        $this->amount = $budget->amount;
        $this->category_id = $budget->category_id;
        $this->start_date = $budget->start_date->format('Y-m-d');
        $this->end_date = $budget->end_date->format('Y-m-d');
        $this->showBudgetModal = true;
    }

    /**
     * Update an existing budget in the database.
     */
    public function updateBudget()
    {
        $this->validate();

        if ($this->editingBudgetId) {
            $budget = Budget::where('user_id', Auth::id())->findOrFail($this->editingBudgetId);
            $budget->update([
                'amount' => $this->amount,
                'category_id' => $this->category_id ?: null,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]);

            // session()->flash('success', 'Budget updated successfully!');
            Toaster::success('Budget updated successfully!');
            $this->showBudgetModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete a budget from the database.
     *
     * @param int $budgetId
     */
    public function deleteBudget(int $budgetId)
    {
        // For budgets, direct deletion is usually fine as they track spending,
        // not directly linked to transactions like expenses/income.
        // The foreign key constraint on category_id in budgets (onDelete('cascade'))
        // ensures that if a category related to a budget is deleted, the budget is too.
        // No complex integrity checks needed here usually, unless you want to warn
        // if a budget has been partially used (e.g., if total expenses exceed 0 for this budget period).
        // For simplicity, we'll just delete it.

        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $budget->delete();
        // session()->flash('success', 'Budget deleted successfully!');
        Toaster::success('Budget deleted successfully!');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = Budget::where('user_id', Auth::id())
                        ->with('category'); // Eager load category for display

        // Apply search filter (e.g., by category name)
        if ($this->search) {
            $query->whereHas('category', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })->orWhere('amount', 'like', '%' . $this->search . '%');
        }

        // Apply category filter
        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        // Apply status filter
        if ($this->filterStatus === 'active') {
            $query->where('end_date', '>=', now()->toDateString())
                  ->where('start_date', '<=', now()->toDateString());
        } elseif ($this->filterStatus === 'past') {
            $query->where('end_date', '<', now()->toDateString());
        } elseif ($this->filterStatus === 'future') {
            $query->where('start_date', '>', now()->toDateString());
        }

        $budgets = $query->orderBy('start_date', 'desc')->paginate(10);

        // Fetch categories for the dropdown in the modal
        $categories = Category::where('user_id', Auth::id())
                              ->orWhereNull('user_id') // Include global categories
                              ->where('type', 'expense') // Only show expense categories for budgeting
                              ->orderBy('name')
                              ->get();

        return view('livewire.manage-budget', [
            'budgets' => $budgets,
            'categories' => $categories,
        ]);
    }
}
