<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ManagePaymentMethods extends Component
{
    use WithPagination; // Enable pagination for payment methods

    // Properties for payment method form
    public $name = '';
    public $description = '';
    public $editingPaymentMethodId = null; // Stores the ID of the payment method being edited

    // Properties for modal state
    public $showPaymentMethodModal = false;

    // Property for search
    public $search = '';

    protected $paginationTheme = 'tailwind'; // Use Tailwind for pagination styling

    /**
     * Validation rules for the payment method form.
     */
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure name is unique per user
                function ($attribute, $value, $fail) {
                    $query = PaymentMethod::where('name', $value)
                                         ->where('user_id', Auth::id());

                    // If editing, exclude the current payment method from the unique check
                    if ($this->editingPaymentMethodId) {
                        $query->where('id', '!=', $this->editingPaymentMethodId);
                    }

                    if ($query->exists()) {
                        $fail('The payment method name "' . $value . '" already exists.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Listen for changes to search property to reset pagination.
     */
    public function updatedSearch()
    {
        $this->resetPage(); // Reset pagination when search changes
    }

    /**
     * Reset form fields and validation errors.
     */
    public function resetForm()
    {
        $this->name = '';
        $this->description = '';
        $this->editingPaymentMethodId = null;
        $this->resetValidation(); // Clear validation errors
    }

    /**
     * Open the modal for creating a new payment method.
     */
    public function createPaymentMethod()
    {
        $this->resetForm(); // Clear any previous form data
        $this->showPaymentMethodModal = true;
    }

    /**
     * Store a new payment method in the database.
     */
    public function storePaymentMethod()
    {
        $this->validate(); // Run validation

        PaymentMethod::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        session()->flash('success', 'Payment method added successfully!');
        $this->showPaymentMethodModal = false; // Close modal
        $this->resetForm(); // Reset form for next entry
    }

    /**
     * Open the modal and populate form for editing an existing payment method.
     *
     * @param int $paymentMethodId
     */
    public function editPaymentMethod(int $paymentMethodId)
    {
        $paymentMethod = PaymentMethod::where('user_id', Auth::id())->findOrFail($paymentMethodId);

        $this->editingPaymentMethodId = $paymentMethod->id;
        $this->name = $paymentMethod->name;
        $this->description = $paymentMethod->description;
        $this->showPaymentMethodModal = true;
    }

    /**
     * Update an existing payment method in the database.
     */
    public function updatePaymentMethod()
    {
        $this->validate(); // Run validation

        if ($this->editingPaymentMethodId) {
            $paymentMethod = PaymentMethod::where('user_id', Auth::id())->findOrFail($this->editingPaymentMethodId);
            $paymentMethod->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            session()->flash('success', 'Payment method updated successfully!');
            $this->showPaymentMethodModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete a payment method from the database.
     *
     * @param int $paymentMethodId
     */
    public function deletePaymentMethod(int $paymentMethodId)
    {
        $paymentMethod = PaymentMethod::where('user_id', Auth::id())->findOrFail($paymentMethodId);

        // Check if there are any associated expenses.
        // The 'set null' onDelete constraint on payment_method_id in expenses
        // will mean that if a payment method is deleted, the expense's payment_method_id
        // will be set to null. This prevents integrity constraint violations.
        try {
            $paymentMethod->delete();
            session()->flash('success', 'Payment method deleted successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            // This specific error handling might not be strictly necessary with 'set null'
            // but is good practice to catch potential issues if constraints change or
            // if you have other relationships where 'restrict' is used.
            if ($e->getCode() === '23000') { // SQLSTATE for Integrity constraint violation
                session()->flash('error', 'Cannot delete payment method: It is currently linked to one or more expenses. Related expenses will have their payment method set to "N/A".');
            } else {
                session()->flash('error', 'An error occurred while deleting the payment method.');
            }
        }
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = PaymentMethod::where('user_id', Auth::id()); // Always filter by authenticated user

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        $paymentMethods = $query->orderBy('name')->paginate(10); // Paginate results

        return view('livewire.manage-payment-methods', [
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
