<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class ManageCategory extends Component
{
    use WithPagination; // Enable pagination for categories

    // Properties for category form
    public $name = '';
    public $type = 'expense'; // Default to 'expense'
    public $description = '';
    public $editingCategoryId = null; // Stores the ID of the category being edited

    // Properties for modal state
    public $showCategoryModal = false;

    // Properties for search and filter
    public $search = '';
    public $filterType = 'all'; // 'all', 'expense', 'income'

    // Validation rules
    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Ensure name is unique per user and type, or globally for global categories
                function ($attribute, $value, $fail) {
                    $query = Category::where('name', $value)
                                     ->where('type', $this->type)
                                     ->where('user_id', Auth::id()); // Check for user's own categories

                    // If editing, exclude the current category from the unique check
                    if ($this->editingCategoryId) {
                        $query->where('id', '!=', $this->editingCategoryId);
                    }

                    if ($query->exists()) {
                        $fail('The category name "' . $value . '" already exists for this type.');
                    }
                },
            ],
            'type' => ['required', 'string', 'in:expense,income'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Listen for changes to search or filter properties to reset pagination.
     */
    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filterType'])) {
            $this->resetPage(); // Reset pagination when search or filter changes
        }
    }

    /**
     * Reset form fields.
     */
    public function resetForm()
    {
        $this->name = '';
        $this->type = 'expense';
        $this->description = '';
        $this->editingCategoryId = null;
        $this->resetValidation(); // Clear validation errors
    }

    /**
     * Open the modal for creating a new category.
     */
    public function createCategory()
    {
        $this->resetForm(); // Clear any previous form data
        $this->showCategoryModal = true;
    }

    /**
     * Store a new category in the database.
     */
    public function storeCategory()
    {
        $this->validate(); // Run validation

        Category::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
        ]);

        session()->flash('success', 'Category added successfully!');
        $this->showCategoryModal = false; // Close modal
        $this->resetForm(); // Reset form for next entry
    }

    /**
     * Open the modal and populate form for editing an existing category.
     *
     * @param int $categoryId
     */
    public function editCategory(int $categoryId)
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->type = $category->type;
        $this->description = $category->description;
        $this->showCategoryModal = true;
    }

    /**
     * Update an existing category in the database.
     */
    public function updateCategory()
    {
        $this->validate(); // Run validation

        if ($this->editingCategoryId) {
            $category = Category::where('user_id', Auth::id())->findOrFail($this->editingCategoryId);
            $category->update([
                'name' => $this->name,
                'type' => $this->type,
                'description' => $this->description,
            ]);

            session()->flash('success', 'Category updated successfully!');
            $this->showCategoryModal = false;
            $this->resetForm();
        }
    }

    /**
     * Delete a category from the database.
     *
     * @param int $categoryId
     */
    public function deleteCategory(int $categoryId)
    {
        $category = Category::where('user_id', Auth::id())->findOrFail($categoryId);

        // Check if there are any associated expenses or budgets or recurring transactions
        // The 'restrict' onDelete constraint on categories_id in expenses/budgets/recurring_transactions
        // will prevent deletion if related records exist.
        // We'll catch the integrity constraint violation and show a user-friendly message.
        try {
            $category->delete();
            session()->flash('success', 'Category deleted successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') { // SQLSTATE for Integrity constraint violation
                session()->flash('error', 'Cannot delete category: It is currently linked to expenses, budgets, or recurring transactions. Please update or delete those first.');
            } else {
                session()->flash('error', 'An error occurred while deleting the category.');
            }
        }
    }

    /**
     * Render the component's view.
     */
    public function render()
    {
        $query = Category::where('user_id', Auth::id()); // Always filter by authenticated user

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply type filter
        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        $categories = $query->orderBy('name')->paginate(10); // Paginate results

        return view('livewire.manage-category', [
            'categories' => $categories,
        ]);
    }
}
