<?php

namespace App\Console\Commands;

use App\Models\Income;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\RecurringTransaction;

class ProcessRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-recurring-transactions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes recurring transactions to create actual expense/income records.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to process recurring transactions...');

        // Fetch recurring transactions that are due today or in the past, and are not yet ended
        $dueTransactions = RecurringTransaction::whereNotNull('next_occurrence_date')
            ->where('next_occurrence_date', '<=', now()->toDateString())
            ->get();

        if ($dueTransactions->isEmpty()) {
            $this->info('No recurring transactions are due today.');
            return Command::SUCCESS;
        }

        $processedCount = 0;

        foreach ($dueTransactions as $recurringTransaction) {
            try {
                // Ensure the recurring transaction is active and not already ended
                if ($recurringTransaction->end_date && $recurringTransaction->end_date->isBefore(now()->startOfDay())) {
                    // If the transaction's end date has passed, mark next_occurrence_date as null
                    // and skip processing. This handles cases where a transaction might have been missed.
                    $recurringTransaction->next_occurrence_date = null;
                    $recurringTransaction->save();
                    $this->warn("Skipping processing for recurring transaction ID {$recurringTransaction->id} as its end date has passed.");
                    continue;
                }

                // Create the actual Expense or Income record
                if ($recurringTransaction->type === 'expense') {
                    Expense::create([
                        'user_id' => $recurringTransaction->user_id,
                        'amount' => $recurringTransaction->amount,
                        'category_id' => $recurringTransaction->category_id,
                        'payment_method_id' => $recurringTransaction->payment_method_id,
                        'description' => $recurringTransaction->description . ' (Recurring)',
                        'expense_date' => $recurringTransaction->next_occurrence_date, // Use the scheduled date
                    ]);
                    $this->info("Created expense for user {$recurringTransaction->user_id} (ID: {$recurringTransaction->id}) for {$recurringTransaction->amount}.");

                } elseif ($recurringTransaction->type === 'income') {
                    Income::create([
                        'user_id' => $recurringTransaction->user_id,
                        'amount' => $recurringTransaction->amount,
                        'source' => $recurringTransaction->description . ' (Recurring)', // Use description as source
                        'description' => $recurringTransaction->description . ' (Recurring)',
                        'income_date' => $recurringTransaction->next_occurrence_date, // Use the scheduled date
                    ]);
                    $this->info("Created income for user {$recurringTransaction->user_id} (ID: {$recurringTransaction->id}) for {$recurringTransaction->amount}.");
                }

                // Calculate the next occurrence date
                $currentOccurrenceDate = $recurringTransaction->next_occurrence_date;
                $nextDate = $currentOccurrenceDate->copy();

                switch ($recurringTransaction->frequency) {
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
                        // This case should ideally not be reached due to validation
                        $this->error("Unknown frequency for recurring transaction ID: {$recurringTransaction->id}");
                        $nextDate = null; // Mark as ended if frequency is invalid
                        break;
                }

                // If next occurrence date exceeds end_date, set it to null to mark as ended
                if ($recurringTransaction->end_date && $nextDate && $nextDate->isAfter($recurringTransaction->end_date)) {
                    $recurringTransaction->next_occurrence_date = null;
                    $this->info("Recurring transaction ID {$recurringTransaction->id} has reached its end date. Marked as ended.");
                } else {
                    $recurringTransaction->next_occurrence_date = $nextDate;
                }

                $recurringTransaction->save();
                $processedCount++;

            } catch (\Exception $e) {
                $this->error("Error processing recurring transaction ID {$recurringTransaction->id}: " . $e->getMessage());
                // Log the exception for detailed debugging
                Log::error("Recurring Transaction Processing Error for ID {$recurringTransaction->id}: " . $e->getMessage(), ['exception' => $e]);
            }
        }

        $this->info("Finished processing. Total {$processedCount} recurring transactions processed.");

        return Command::SUCCESS;
    }
}
