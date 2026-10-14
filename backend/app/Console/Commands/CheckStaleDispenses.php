<?php

namespace App\Console\Commands;

use App\Models\Dispense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckStaleDispenses extends Command
{
    protected $signature = 'vending:check-stale-dispenses
                            {--minutes=3 : Minimum age in minutes before reporting a dispense}';

    protected $description =
    'Report stale dispensing processes without changing stock or status.';

    public function handle(): int
    {
        $minutes = filter_var(
            $this->option('minutes'),
            FILTER_VALIDATE_INT
        );

        if ($minutes === false || $minutes < 1) {
            $this->error(
                'The --minutes option must be an integer greater than zero.'
            );

            return self::FAILURE;
        }

        $cutoff = now()->subMinutes($minutes);

        $dispenses = Dispense::query()
            ->with(['order', 'machine', 'slot'])
            ->where('status', 'DISPENSING')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', $cutoff)
            ->orderBy('started_at')
            ->get();

        if ($dispenses->isEmpty()) {
            $this->info(
                "No stale dispenses found (threshold: {$minutes} minutes)."
            );

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($dispenses as $dispense) {
            $ageMinutes = max(
                0,
                (int) floor(
                    $dispense->started_at->diffInSeconds(now()) / 60
                )
            );

            $rows[] = [
                $dispense->id,
                $dispense->order?->order_code ?? '-',
                $dispense->machine?->machine_code ?? '-',
                $dispense->slot?->slot_code ?? '-',
                $dispense->started_at->toDateTimeString(),
                $ageMinutes,
            ];

            Log::warning(
                'Stale dispense detected; manual reconciliation required.',
                [
                    'dispense_id' => $dispense->id,
                    'order_id' => $dispense->order_id,
                    'machine_id' => $dispense->machine_id,
                    'slot_id' => $dispense->slot_id,
                    'started_at' => $dispense->started_at,
                    'age_minutes' => $ageMinutes,
                    'threshold_minutes' => $minutes,
                ]
            );
        }

        $this->warn(
            count($rows) .
                ' stale dispense(s) found. Manual reconciliation required.'
        );

        $this->table(
            [
                'Dispense ID',
                'Order Code',
                'Machine',
                'Slot',
                'Started At',
                'Age (min)',
            ],
            $rows
        );

        $this->comment(
            'No stock or transaction status was changed.'
        );

        return self::SUCCESS;
    }
}
