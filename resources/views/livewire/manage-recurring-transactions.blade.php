<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div
        class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6 md:p-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 mb-6">Manage Recurring Transactions</h2>

        <!-- Success/Error Message Display -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 dark:bg-green-800/30 dark:border-green-900 dark:text-green-500"
                role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 dark:bg-red-800/30 dark:border-red-900 dark:text-red-500"
                role="alert">
                {{ session('error') }}
            </div>
        @endif

        <!-- Header with Add Button, Search, and Filters -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <button wire:click="createRecurringTransaction"
                class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="flex-shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M12 5v14m7-7H5" />
                </svg>
                Add New Recurring Transaction
            </button>

            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Search recurring transactions..."
                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 flex-1 min-w-[150px]">
                <select wire:model.live="filterType"
                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 flex-1 min-w-[120px]">
                    <option value="all">All Types</option>
                    <option value="expense">Expense</option>
                    <option value="income">Income</option>
                </select>
                <select wire:model.live="filterFrequency"
                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 flex-1 min-w-[120px]">
                    <option value="all">All Frequencies</option>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <select wire:model.live="filterCategoryId"
                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 flex-1 min-w-[150px]">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus"
                    class="py-2 px-3 block border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400 flex-1 min-w-[120px]">
                    <option value="active">Active</option>
                    <option value="future">Future</option>
                    <option value="ended">Ended</option>
                    <option value="all">All Statuses</option>
                </select>
            </div>
        </div>

        <!-- Recurring Transactions Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead class="bg-gray-50 dark:bg-neutral-800">
                    <tr>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Amount</th>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Type</th>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Category</th>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Frequency</th>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Next Occurrence</th>
                        <th scope="col"
                            class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Description</th>
                        <th scope="col"
                            class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @forelse ($recurringTransactions as $transaction)
                        <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                            <td
                                class="px-6 py-4 whitespace-nowrap text-sm font-medium @if($transaction->type === 'expense') text-red-600 @else text-green-600 @endif">
                                {{ $transaction->type === 'expense' ? '-' : '+' }}{{ app_currency() }}{{ number_format($transaction->amount, 2) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                {{ ucfirst($transaction->type) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                {{ $transaction->category->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                {{ ucfirst($transaction->frequency) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                @if($transaction->next_occurrence_date)
                                    {{ date('M d, Y', strtotime($transaction->next_occurrence_date)) }}
                                @else
                                    Ended
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                {{ $transaction->description ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                <button wire:click="editRecurringTransaction({{ $transaction->id }})"
                                    class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400">
                                    Edit
                                </button>
                                <button wire:click="deleteRecurringTransaction({{ $transaction->id }})"
                                    class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-red-600 hover:text-red-800 disabled:opacity-50 disabled:pointer-events-none dark:text-red-500 dark:hover:text-red-400 ml-3">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7"
                                class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200 text-center">
                                No recurring transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $recurringTransactions->links() }}
        </div>

        <!-- Recurring Transaction Add/Edit Modal -->
        @if ($showRecurringTransactionModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                    <!-- This element is to trick the browser into centering the modal contents. -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div
                        class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full dark:bg-neutral-900">
                        <form
                            wire:submit.prevent="{{ $editingRecurringTransactionId ? 'updateRecurringTransaction' : 'storeRecurringTransaction' }}">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-neutral-900">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-neutral-200" id="modal-title">
                                    {{ $editingRecurringTransactionId ? 'Edit Recurring Transaction' : 'Add New Recurring Transaction' }}
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="recurring-amount"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Amount</label>
                                        <input type="number" id="recurring-amount" wire:model.defer="amount" step="0.01"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400"
                                            placeholder="0.00">
                                        @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="recurring-type"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Type</label>
                                        <select id="recurring-type" wire:model.live="type"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                            <option value="expense">Expense</option>
                                            <option value="income">Income</option>
                                        </select>
                                        @error('type') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="recurring-category"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Category
                                            (Optional)</label>
                                        <select id="recurring-category" wire:model.defer="category_id"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                            <option value="">Select Category</option>
                                            @foreach($categories->where('type', $type) as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('category_id') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    @if ($type === 'expense')
                                        <div>
                                            <label for="recurring-payment-method"
                                                class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Payment
                                                Method (Optional)</label>
                                            <select id="recurring-payment-method" wire:model.defer="payment_method_id"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                                <option value="">Select Payment Method</option>
                                                @foreach($paymentMethods as $pm)
                                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('payment_method_id') <span
                                            class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                        </div>
                                    @endif
                                    <div>
                                        <label for="recurring-description"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Description
                                            (Optional)</label>
                                        <textarea id="recurring-description" wire:model.defer="description" rows="3"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400"
                                            placeholder="e.g., Monthly electricity bill"></textarea>
                                        @error('description') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="recurring-frequency"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Frequency</label>
                                        <select id="recurring-frequency" wire:model.defer="frequency"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                            <option value="daily">Daily</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                        @error('frequency') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="recurring-start-date"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">Start
                                            Date</label>
                                        <input type="date" id="recurring-start-date" wire:model.defer="start_date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                        @error('start_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="recurring-end-date"
                                            class="block text-sm font-medium text-gray-700 dark:text-neutral-400">End Date
                                            (Optional)</label>
                                        <input type="date" id="recurring-end-date" wire:model.defer="end_date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm py-2 px-3 focus:ring-blue-500 focus:border-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                                        @error('end_date') <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse dark:bg-neutral-800">
                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    {{ $editingRecurringTransactionId ? 'Update Recurring Transaction' : 'Save Recurring Transaction' }}
                                </button>
                                <button type="button" wire:click="$set('showRecurringTransactionModal', false)"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm dark:bg-neutral-700 dark:text-neutral-200 dark:border-neutral-600 dark:hover:bg-neutral-600">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>