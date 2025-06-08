<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\RecurringTransaction;
use App\Models\Category; // Needed to populate category dropdown
use App\Models\PaymentMethod; // Needed to populate payment method dropdown
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Carbon\Carbon;

class ManageRecurringTransactions extends Component
{
    use WithPagination; // Enable pagination for recurring transactions

    // Properties for the recurring transaction form
    public $amount = 0.00;
    public $type = 'expense'; // 'expense' or 'income'
    public $description = '';
    public $frequency = 'monthly'; // 'daily', 'weekly', 'monthly', 'yearly'
    public $start_date;
    public $end_date; // Nullable, for indefinite recurring transactions
    public $category_id = ''; // Nullable
    public $payment_method_id = ''; // Nullable (primarily for recurring expenses)

    // Properties for modal state
    public $editingRecurringTransactionId = null; // Stores the ID of the transaction being edited
    public $showRecurringTransactionModal = false;

    // Properties for search and filter
    public $search = '';
    public $filterType = 'all'; // 'all', 'expense', 'income'
    public $filterFrequency = 'all'; // 'all', 'daily', 'weekly', 'monthly', 'yearly'
    public $filterCategoryId = ''; // Filter by category
    public $filterStatus = 'active'; // 'active', 'ended', 'future', 'all'

    protected $paginationTheme = 'tailwind'; // Use Tailwind for pagination styling

    /**
     * Mount lifecycle hook to set initial date values.
     */
    public function mount()
    {
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = null; // Default to no end date
    }

    /**
     * Validation rules for the recurring transaction form.
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'string', 'in:expense,income'],
            'description' => ['nullable', 'string', 'max:500'],
            'frequency' => ['required', 'string', 'in:daily,weekly,monthly,yearly'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'category_id' => [
                'nullable',
                'exists:categories,id',
                // Custom rule to ensure category belongs to the user or is global and matches transaction type
                function ($attribute, $value, $fail) {
                    if ($value && Auth::check()) {
                        $category = Category::find($value);
                        if (!$category || ($category->user_id !== Auth::id() && $category->user_id !== null) || $category->type !== $this->type) {
                            $fail('The selected category is invalid, does not belong to you, or does not match the transaction type (' . ucfirst($this->type) . ').');
                        }
                    }
                },
            ],
            'payment_method_id' => [
                'nullable', // Payment method is optional for recurring transactions
                'exists:payment_methods,id',
                // Custom rule to ensure payment method belongs to the user if not null
                function ($attribute, $value, $fail) {
                    if ($value && Auth::check() && !PaymentMethod::where('id', $value)->where('user_id', Auth::id())->exists()) {
                        $fail('The selected payment method is invalid or does not belong to you.');
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
        if (in_array($propertyName, ['search', 'filterType', 'filterFrequency', 'filterCategoryId', 'filterStatus'])) {
            $this->resetPage(); // Reset pagination when search or filter changes
        }
        // If the 'type' changes, re-validate 'category_id' to ensure the category type matches
        if ($propertyName === 'type') {
            $this->validateOnly('category_id');
        }
    }

    /**
     * Reset form fields and validation errors.
     */
    public function resetForm()
    {
        $this->amount = 0.00;
        $this->type = 'expense';
        $this->description = '';
        $this->frequency = 'monthly';
        $this->start_date = now()->format('Y-m-d');
        $this->end_date = null;
        $this->category_id = '';
        $this->payment_method_id = '';
        $this->editingRecurringTransactionId = null;
        $this->resetValidation(); // Clear validation errors
    }

    /**
     * Open the modal for creating a new recurring transaction.
     */
    public function createRecurringTransaction()
    {
        $this->resetForm(); // Clear any previous form data
        $this->showRecurringTransactionModal = true;
    }

    /**
     * Store a new recurring transaction in the database.
     */
    public function storeRecurringTransaction()
    {
        $this->validate(); // Run validation

        // Calculate the initial next_occurrence_date
        $nextOccurrenceDate = $this->calculateNextOccurrenceDate($this->start_date, $this->frequency, $this->end_date);

        RecurringTransaction::create([
            'user_id' => Auth::id(),
            'amount' => $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'frequency' => $this->frequency,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'category_id' => $this->category_id ?: null,
            'payment_method_id' => $this->payment_method_id ?: null,
            'next_occurrence_date' => $nextOccurrenceDate, // Set the calculated next occurrence
        ]);

        session()->flash('success', 'Recurring transaction added successfully!');
        $this->showRecurringTransactionModal = false; // Close modal
        $this->resetForm(); // Reset form for next entry
    }

    /**
     * Open the modal and populate form for editing an existing recurring transaction.
     *
     * @param int $recurringTransactionId
     */
    public function editRecurringTransaction(int $recurringTransactionId)
    {
        $transaction = RecurringTransaction::where('user_id', Auth::id())->findOrFail($recurringTransactionId);

        $this->editingRecurringTransactionId = $transaction->id;
        $this->amount = $transaction->amount;
        $this->type = $transaction->type;
        $this->description = $transaction->description;
        $this->frequency = $transaction->frequency;
        $this->start_date = $transaction->start_date;
        $this->end_date = $transaction->end_date ? $transaction->end_date : null; // Handle nullable end_date
        $this->category_id = $transaction->category_id;
        $this->payment_method_id = $transaction->payment_method_id;
        $this->showRecurringTransactionModal = true;
    }

    /**
     * Update an existing recurring transaction in the database.
     */
    public function updateRecurringTransaction()
    {
        $this->validate(); // Run validation

        if ($this->editingRecurringTransactionId) {
            $transaction = RecurringTransaction::where('user_id', Auth::id())->findOrFail($this->editingRecurringTransactionId);

            // Recalculate next occurrence date if start_date or frequency changes
            // Or if an end_date was added/removed, or if the next occurrence date somehow became invalid.
            $nextOccurrenceDate = $this->calculateNextOccurrenceDate($this->start_date, $this->frequency, $this->end_date);

            $transaction->update([
                'amount' => $this->amount,
                'type' => $this->type,
                'description' => $this->description,
                'frequency' => $this->frequency,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'category_id' => $this->category_id ?: null,
                'payment_method_id' => $this->payment_method_id ?: null,
                'next_occurrence_date' => $nextOccurrenceDate, // Update the next occurrence date
            ]);

            session()->flash('success', 'Recurring transaction updated successfully!');
            $this->showRecurringTransactionModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete a recurring transaction from the database.
     *
     * @param int $recurringTransactionId
     */
    public function deleteRecurringTransaction(int $recurringTransactionId)
    {
        $transaction = RecurringTransaction::where('user_id', Auth::id())->findOrFail($recurringTransactionId);
        // The foreign key constraints (onDelete('set null') for category_id and payment_method_id)
        // are handled at the database level. Direct deletion is fine here.
        $transaction->delete();
        session()->flash('success', 'Recurring transaction deleted successfully!');
    }

    /**
     * Helper to calculate the next occurrence date for a recurring transaction.
     * This is a simplified logic. For a robust solution, consider a dedicated scheduling library.
     * It aims to find the first occurrence date that is on or after today,
     * respecting the start_date, frequency, and end_date.
     *
     * @param string $startDateString
     * @param string $frequency
     * @param string|null $endDateString
     * @return string|null
     */
    private function calculateNextOccurrenceDate(string $startDateString, string $frequency, ?string $endDateString): ?string
    {
        $startDate = Carbon::parse($startDateString);
        $endDate = $endDateString ? Carbon::parse($endDateString) : null;
        $today = now()->startOfDay();

        $nextDate = $startDate->copy();

        // If the start date is in the future, the next occurrence is the start date itself.
        if ($startDate->isFuture()) {
            return $startDate->format('Y-m-d');
        }

        // Advance nextDate until it's today or in the future
        while ($nextDate->isBefore($today)) {
            switch ($frequency) {
                case 'daily':
                    $nextDate->addDay();
                    break;
                case 'weekly':
                    $nextDate->addWeek();
                    break;
                case 'monthly':
                    $nextDate->addMonth();
                    break;
                case 'yearly':
                    $nextDate->addYear();
                    break;
                default:
                    // Should not happen due to validation, but a fallback
                    return null;
            }

            // If advancing the date pushes it past the end_date, then there are no more occurrences
            if ($endDate && $nextDate->isAfter($endDate)) {
                return null;
            }
        }

        // If the calculated nextDate is past the end_date, return null
        if ($endDate && $nextDate->isAfter($endDate)) {
            return null;
        }

        return $nextDate->format('Y-m-d');
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = RecurringTransaction::where('user_id', Auth::id())
                                     ->with('category', 'paymentMethod'); // Eager load relationships for display

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('amount', 'like', '%' . $this->search . '%')
                  ->orWhereHas('category', function ($cq) {
                      $cq->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('paymentMethod', function ($pmq) {
                      $pmq->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply type filter
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        // Apply frequency filter
        if ($this->filterFrequency !== 'all') {
            $query->where('frequency', $this->filterFrequency);
        }

        // Apply category filter
        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        // Apply status filter
        if ($this->filterStatus === 'active') {
            // Active means it has started and either no end date or end date is in the future/today
            // And its next occurrence is on or after today (or within a reasonable window, e.g., 1 year)
            $query->where('start_date', '<=', now()->toDateString())
                  ->where(function ($q) {
                      $q->whereNull('end_date')
                        ->orWhere('end_date', '>=', now()->toDateString());
                  });
        } elseif ($this->filterStatus === 'ended') {
            // Ended means the end_date has passed
            $query->where('end_date', '<', now()->toDateString());
        } elseif ($this->filterStatus === 'future') {
            // Future means the start_date is in the future
            $query->where('start_date', '>', now()->toDateString());
        }

        $recurringTransactions = $query->orderBy('next_occurrence_date', 'asc')->paginate(10);

        // Fetch categories for dropdown (filtered by selected type)
        // Note: When adding, category type should match selected transaction type.
        // For filtering, all categories can be listed.
        $categories = Category::where('user_id', Auth::id())
                              ->orWhereNull('user_id') // Include global categories
                              ->orderBy('name')
                              ->get();

        // Fetch payment methods for dropdown (user-specific)
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())
                                       ->orderBy('name')
                                       ->get();

        return view('livewire.manage-recurring-transactions', [
            'recurringTransactions' => $recurringTransactions,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
