<div class="px-4 py-6 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6">

        <!-- Active Budgets Card -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-4">Active Budgets</h3>
            @if ($activeBudgets->isNotEmpty())
                <ul class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach ($activeBudgets as $budget)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                    {{ $budget->category ? $budget->category->name : 'Overall Budget' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">
                                    {{ $budget->start_date->format('M d') }} - {{ $budget->end_date->format('M d, Y') }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-neutral-200">
                                {{ app_currency() }}{{ number_format($budget->amount, 2) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-end">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium dark:text-blue-500 dark:hover:text-blue-400">View All Budgets &rarr;</a>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-neutral-400">No active budgets found.</p>
                <div class="mt-4 text-end">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium dark:text-blue-500 dark:hover:text-blue-400">Create a new budget &rarr;</a>
                </div>
            @endif
        </div>

        <!-- Recent Expenses Card -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-4">Recent Expenses</h3>
            @if ($recentExpenses->isNotEmpty())
                <ul class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach ($recentExpenses as $expense)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                    {{ $expense->description ?: $expense->category->name ?? 'Uncategorized Expense' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">
                                    {{ $expense->expense_date->format('M d, Y') }} &bull; {{ $expense->category->name ?? 'N/A' }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-red-600">
                                - {{ app_currency() }}{{ number_format($expense->amount, 2) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-end">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium dark:text-blue-500 dark:hover:text-blue-400">View All Expenses &rarr;</a>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-neutral-400">No recent expenses found.</p>
            @endif
        </div>

        <!-- Recent Income Card -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-4">Recent Income</h3>
            @if ($recentIncome->isNotEmpty())
                <ul class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach ($recentIncome as $income)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                    {{ $income->source ?: 'General Income' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">
                                    {{ $income->income_date->format('M d, Y') }}
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-green-600">
                                + {{ app_currency() }}{{ number_format($income->amount, 2) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-end">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium dark:text-blue-500 dark:hover:text-blue-400">View All Income &rarr;</a>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-neutral-400">No recent income found.</p>
            @endif
        </div>

        <!-- Upcoming Recurring Transactions Card -->
        <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-neutral-200 mb-4">Upcoming Recurring</h3>
            @if ($upcomingRecurringTransactions->isNotEmpty())
                <ul class="divide-y divide-gray-200 dark:divide-neutral-700">
                    @foreach ($upcomingRecurringTransactions as $transaction)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-neutral-200">
                                    {{ $transaction->description ?: ($transaction->category->name ?? 'Recurring Item') }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-neutral-400">
                                    {{ date('M d, Y', strtotime($transaction->next_occurrence_date)) }} &bull; {{ ucfirst($transaction->type) }} ({{ ucfirst($transaction->frequency) }})
                                </p>
                            </div>
                            <p class="text-sm font-semibold @if($transaction->type === 'expense') text-red-600 @else text-green-600 @endif">
                                {{ $transaction->type === 'expense' ? '-' : '+' }}${{ number_format($transaction->amount, 2) }}
                            </p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 text-end">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium dark:text-blue-500 dark:hover:text-blue-400">View All Recurring &rarr;</a>
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-neutral-400">No upcoming recurring transactions.</p>
            @endif
        </div>

    </div>
</div>
