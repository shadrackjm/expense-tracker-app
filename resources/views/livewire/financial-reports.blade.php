<div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
    <div class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-800 p-6 md:p-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-neutral-200 mb-6">Financial Reports</h2>

        <!-- Success/Error Message Display -->
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4 dark:bg-green-800/30 dark:border-green-900 dark:text-green-500" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4 dark:bg-red-800/30 dark:border-red-900 dark:text-red-500" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="bg-yellow-100 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg mb-4 dark:bg-yellow-800/30 dark:border-yellow-900 dark:text-yellow-500" role="alert">
                {{ session('warning') }}
            </div>
        @endif

        <!-- Report Filters -->
        <div class="mb-8 pb-8 border-b border-gray-200 dark:border-neutral-700">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">Report Options</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {{-- Report Type Selector --}}
                <div>
                    <label for="report_type" class="block text-sm font-medium mb-2 dark:text-white">Report Type</label>
                    <select id="report_type" wire:model.live="reportType" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                        <option value="expense_summary">Expense Summary by Category</option>
                        <option value="income_expense_trend">Income vs. Expense Trend (Yearly)</option>
                        <option value="budget_vs_actual">Budget vs. Actual</option>
                        <option value="transaction_list">Transaction List</option>
                    </select>
                </div>

                {{-- Period Selector --}}
                <div>
                    <label for="report_period" class="block text-sm font-medium mb-2 dark:text-white">Period</label>
                    <select id="report_period" wire:model.live="reportPeriod" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                {{-- Conditional Date Filters --}}
                @if ($reportPeriod === 'monthly')
                    <div>
                        <label for="selected_month" class="block text-sm font-medium mb-2 dark:text-white">Month</label>
                        <select id="selected_month" wire:model.live="selectedMonth" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                            @foreach($months as $num => $name)
                                <option value="{{ $num }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="selected_year_monthly" class="block text-sm font-medium mb-2 dark:text-white">Year</label>
                        <select id="selected_year_monthly" wire:model.live="selectedYear" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($reportPeriod === 'yearly')
                    <div>
                        <label for="selected_year_yearly" class="block text-sm font-medium mb-2 dark:text-white">Year</label>
                        <select id="selected_year_yearly" wire:model.live="selectedYear" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                            @foreach($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($reportPeriod === 'custom')
                    <div>
                        <label for="custom_start_date" class="block text-sm font-medium mb-2 dark:text-white">Start Date</label>
                        <input type="date" id="custom_start_date" wire:model.live="customStartDate" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                    </div>
                    <div>
                        <label for="custom_end_date" class="block text-sm font-medium mb-2 dark:text-white">End Date</label>
                        <input type="date" id="custom_end_date" wire:model.live="customEndDate" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-neutral-800 dark:border-neutral-700 dark:text-neutral-400">
                    </div>
                @endif
            </div>
            <div class="mt-6">
                <button wire:click="generateReports" class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                    Generate Report
                </button>
            </div>
        </div>

        <!-- Report Display Area -->
        <div class="mt-8">
            {{-- Expense Summary by Category Report --}}
            @if ($reportType === 'expense_summary' && !empty($expenseCategoriesSummary))
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">
                    Expense Summary by Category ({{ Carbon\Carbon::parse($expenseCategoriesSummary['report_period_start'])->format('M d, Y') }} - {{ Carbon\Carbon::parse($expenseCategoriesSummary['report_period_end'])->format('M d, Y') }})
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Category</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Amount</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Percentage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @foreach ($expenseCategoriesSummary['data'] as $data)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $data['category'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($data['amount'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">
                                        @if($expenseCategoriesSummary['total_expenses'] > 0)
                                            {{ number_format(($data['amount'] / $expenseCategoriesSummary['total_expenses']) * 100, 2) }}%
                                        @else
                                            0.00%
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-neutral-800/70 font-bold">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">Total Expenses</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($expenseCategoriesSummary['total_expenses'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">100.00%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            {{-- Income vs. Expense Trend Report --}}
            @elseif ($reportType === 'income_expense_trend' && !empty($incomeExpenseTrend))
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">
                    Income vs. Expense Trend ({{ $incomeExpenseTrend['report_year'] ?? '' }})
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Month</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Total Income</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Total Expenses</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Net Balance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @php
                                $grandTotalIncome = 0;
                                $grandTotalExpenses = 0;
                                $grandNetBalance = 0;
                            @endphp
                            @foreach ($incomeExpenseTrend['data'] as $data)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $data['month_name'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-green-600">${{ number_format($data['total_income'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-red-600">${{ number_format($data['total_expenses'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if($data['net_balance'] >= 0) text-green-600 @else text-red-600 @endif">
                                        ${{ number_format($data['net_balance'], 2) }}
                                    </td>
                                </tr>
                                @php
                                    $grandTotalIncome += $data['total_income'];
                                    $grandTotalExpenses += $data['total_expenses'];
                                    $grandNetBalance += $data['net_balance'];
                                @endphp
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-neutral-800/70 font-bold">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">Grand Totals</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-green-600">${{ number_format($grandTotalIncome, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-red-600">${{ number_format($grandTotalExpenses, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if($grandNetBalance >= 0) text-green-600 @else text-red-600 @endif">
                                    ${{ number_format($grandNetBalance, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            {{-- Budget vs. Actual Report --}}
            @elseif ($reportType === 'budget_vs_actual' && !empty($budgetVsActualReport['data']))
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">
                    Budget vs. Actual ({{ Carbon\Carbon::parse($budgetVsActualReport['report_period_start'])->format('M d, Y') }} - {{ Carbon\Carbon::parse($budgetVsActualReport['report_period_end'])->format('M d, Y') }})
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Category</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Budgeted</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Actual</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Difference</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @foreach ($budgetVsActualReport['data'] as $data)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ $data['category'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($data['budgeted'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($data['actual'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if($data['difference'] >= 0) text-green-600 @else text-red-600 @endif">
                                        ${{ number_format($data['difference'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $data['status'] }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-neutral-800/70 font-bold">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">Totals</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($budgetVsActualReport['total_budgeted'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-gray-800 dark:text-neutral-200">${{ number_format($budgetVsActualReport['total_actual'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if(($budgetVsActualReport['total_difference'] ?? 0) >= 0) text-green-600 @else text-red-600 @endif">
                                    ${{ number_format($budgetVsActualReport['total_difference'] ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200"></td> {{-- Empty for status column --}}
                            </tr>
                        </tbody>
                    </table>
                </div>
            {{-- Transaction List Report --}}
            @elseif ($reportType === 'transaction_list' && !empty($transactionListReport['data']))
                <h3 class="text-xl font-semibold text-gray-800 dark:text-neutral-200 mb-4">
                    Transaction List ({{ Carbon\Carbon::parse($transactionListReport['report_period_start'])->format('M d, Y') }} - {{ Carbon\Carbon::parse($transactionListReport['report_period_end'])->format('M d, Y') }})
                </h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                        <thead class="bg-gray-50 dark:bg-neutral-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Date</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Type</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Description</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Category</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Payment Method</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                            @foreach ($transactionListReport['data'] as $data)
                                <tr class="hover:bg-gray-50 dark:hover:bg-neutral-800/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ Carbon\Carbon::parse($data['date'])->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">{{ ucfirst($data['type']) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $data['description'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $data['category'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">{{ $data['payment_method'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if($data['type'] === 'expense') text-red-600 @else text-green-600 @endif">
                                        {{ $data['type'] === 'expense' ? '-' : '+' }}${{ number_format($data['amount'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="bg-gray-100 dark:bg-neutral-800/70 font-bold">
                                <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">Totals for Period</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-green-600">Income: ${{ number_format($transactionListReport['total_income'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm text-red-600">Expenses: ${{ number_format($transactionListReport['total_expenses'] ?? 0, 2) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm @if(($transactionListReport['net_balance'] ?? 0) >= 0) text-green-600 @else text-red-600 @endif">
                                    Net: ${{ number_format($transactionListReport['net_balance'] ?? 0, 2) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 dark:text-neutral-400">Select a report type and period to generate financial insights.</p>
            @endif
        </div>
    </div>
</div>
