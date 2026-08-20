<?php

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\Transaction;
use Illuminate\Console\Command;

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
    protected $description = 'Genera transacciones a partir de los gastos/ingresos recurrentes vencidos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $due = RecurringTransaction::query()->dueToday()->get();

        $processed = 0;

        foreach ($due as $recurring) {
            if ($recurring->end_date && $recurring->next_due_date->gt($recurring->end_date)) {
                $recurring->update(['is_active' => false]);

                continue;
            }

            $transaction = new Transaction([
                'category_id' => $recurring->category_id,
                'description' => $recurring->name,
                'amount' => $recurring->amount,
                'type' => $recurring->type,
                'date' => $recurring->next_due_date->toDateString(),
            ]);
            $transaction->user_id = $recurring->user_id;
            $transaction->save();

            $recurring->update([
                'next_due_date' => $recurring->calculateNextDueDate(),
            ]);

            $processed++;
        }

        $this->info("Procesadas {$processed} transacciones recurrentes.");

        return self::SUCCESS;
    }
}
