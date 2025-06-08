<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Expense;
use App\Models\Category; // Needed to populate category dropdown
use App\Models\PaymentMethod; // Needed to populate payment method dropdown
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Carbon\Carbon;

class ManageExpense extends Component
{
    use WithPagination; // Enable pagination for expense records

    // Properties for expense form
    public $amount = 0.00;
    public $category_id = '';
    public $payment_method_id = '';
    public $description = '';
    public $expense_date;
    public $editingExpenseId = null; // Stores the ID of the expense record being edited

    // Properties for modal state
    public $showExpenseModal = false;

    // Properties for search and filter
    public $search = '';
    public $filterCategoryId = ''; // Filter by category
    public $filterPaymentMethodId = ''; // Filter by payment method
    public $filterMonth = ''; // Filter by month
    public $filterYear = ''; // Filter by year

    protected $paginationTheme = 'tailwind'; // Use Tailwind for pagination styling

    /**
     * Mount lifecycle hook to set initial date values.
     */
    public function mount()
    {
        $this->expense_date = now()->format('Y-m-d');
        $this->filterYear = now()->year;
        $this->filterMonth = now()->month;
    }

    /**
     * Validation rules for the expense form.
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'category_id' => [
                'required',
                'exists:categories,id',
                // Custom rule to ensure category belongs to the user or is global
                function ($attribute, $value, $fail) {
                    if (Auth::check() && !Category::where('id', $value)
                                                  ->where(function($query) {
                                                      $query->where('user_id', Auth::id())
                                                            ->orWhereNull('user_id'); // Allow global categories
                                                  })
                                                  ->exists()) {
                        $fail('The selected category is invalid or does not belong to you.');
                    }
                },
            ],
            'payment_method_id' => [
                'nullable', // Payment method is optional
                'exists:payment_methods,id',
                // Custom rule to ensure payment method belongs to the user if not null
                function ($attribute, $value, $fail) {
                    if ($value && Auth::check() && !PaymentMethod::where('id', $value)->where('user_id', Auth::id())->exists()) {
                        $fail('The selected payment method is invalid or does not belong to you.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'expense_date' => ['required', 'date'],
        ];
    }

    /**
     * Listen for changes to search or filter properties to reset pagination.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterCategoryId', 'filterPaymentMethodId', 'filterMonth', 'filterYear'])) {
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
        $this->payment_method_id = '';
        $this->description = '';
        $this->expense_date = now()->format('Y-m-d');
        $this->editingExpenseId = null;
        $this->resetValidation(); // Clear validation errors
    }

    /**
     * Open the modal for creating a new expense record.
     */
    public function createExpense()
    {
        $this->resetForm(); // Clear any previous form data
        $this->showExpenseModal = true;
    }

    /**
     * Store a new expense record in the database.
     */
    public function storeExpense()
    {
        $this->validate(); // Run validation

        Expense::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'category_id' => $this->category_id,
            'payment_method_id' => $this->payment_method_id ?: null, // Store null if empty string
            'description' => $this->description,
            'expense_date' => $this->expense_date,
        ]);

        session()->flash('success', 'Expense added successfully!');
        $this->showExpenseModal = false; // Close modal
        $this->resetForm(); // Reset form for next entry
    }

    /**
     * Open the modal and populate form for editing an existing expense record.
     *
     * @param int $expenseId
     */
    public function editExpense(int $expenseId)
    {
        $expense = Expense::where('user_id', Auth::id())->findOrFail($expenseId);

        $this->editingExpenseId = $expense->id;
        $this->amount = $expense->amount;
        $this->category_id = $expense->category_id;
        $this->payment_method_id = $expense->payment_method_id;
        $this->description = $expense->description;
        $this->expense_date = $expense->expense_date->format('Y-m-d'); // Format date for input field
        $this->showExpenseModal = true;
    }

    /**
     * Update an existing expense record in the database.
     */
    public function updateExpense()
    {
        $this->validate(); // Run validation

        if ($this->editingExpenseId) {
            $expense = Expense::where('user_id', Auth::id())->findOrFail($this->editingExpenseId);
            $expense->update([
                'amount' => $this->amount,
                'category_id' => $this->category_id,
                'payment_method_id' => $this->payment_method_id ?: null,
                'description' => $this->description,
                'expense_date' => $this->expense_date,
            ]);

            session()->flash('success', 'Expense updated successfully!');
            $this->showExpenseModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete an expense record from the database.
     *
     * @param int $expenseId
     */
    public function deleteExpense(int $expenseId)
    {
        $expense = Expense::where('user_id', Auth::id())->findOrFail($expenseId);
        // The foreign key constraints (onDelete('restrict') for category_id, onDelete('set null') for payment_method_id)
        // are handled at the database level. Direct deletion is fine here.
        $expense->delete();
        session()->flash('success', 'Expense deleted successfully!');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = Expense::where('user_id', Auth::id())
                        ->with('category', 'paymentMethod'); // Eager load relationships for display

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('category', function ($cq) {
                      $cq->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('paymentMethod', function ($pmq) {
                      $pmq->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhere('amount', 'like', '%' . $this->search . '%');
            });
        }

        // Apply category filter
        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        // Apply payment method filter
        if ($this->filterPaymentMethodId) {
            $query->where('payment_method_id', $this->filterPaymentMethodId);
        }

        // Apply month and year filters (SQLite compatible as per previous fix)
        if ($this->filterYear) {
            $query->whereYear('expense_date', $this->filterYear);
        }
        if ($this->filterMonth) {
            $query->whereMonth('expense_date', $this->filterMonth);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
                          ->orderBy('created_at', 'desc') // For stable sorting on the same date
                          ->paginate(10);

        // Fetch categories for dropdown (expense type and user-specific/global)
        $categories = Category::where('user_id', Auth::id())
                              ->orWhereNull('user_id')
                              ->where('type', 'expense')
                              ->orderBy('name')
                              ->get();

        // Fetch payment methods for dropdown (user-specific)
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
                                       ->orderBy('name')
                                       ->get();

        // Get years with expense data for filter dropdown (SQLite compatible)
        $availableYears = Expense::where('user_id', Auth::id())
                                 ->get()
                                 ->map(function ($expense) {
                                     return $expense->expense_date->year;
                                 })
                                 ->unique()
                                 ->sortDesc();

        // Get months for filter dropdown
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::createFromDate(null, $m, 1)->format('F');
        }

        return view('livewire.manage-expense', [
            'expenses' => $expenses,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'availableYears' => $availableYears,
            'months' => $months,
        ]);
    }
}
