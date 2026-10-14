<?php

namespace App\Services;

use App\Models\Dispense;
use App\Models\DispenseReconciliation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class DispenseReconciliationService
{
    public function __construct(
        private readonly DispenseService $dispenseService
    ) {}

    public function reconcile(
        Dispense $dispense,
        User $user,
        string $decision,
        string $reason,
        ?string $evidence = null
    ): DispenseReconciliation {
        return DB::transaction(function () use (
            $dispense, $user, $decision, $reason, $evidence
        ) {
            $locked = Dispense::query()
                ->whereKey($dispense->id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = $locked->status;

            if ($decision === 'OUTCOME_UNKNOWN') {
                if (! in_array($before, ['PENDING', 'DISPENSING'], true)) {
                    throw ValidationException::withMessages([
                        'decision' => ['Only an active dispense can remain unresolved.'],
                    ]);
                }

                // Tidak mengubah stok, reservasi, atau status dispense.
                $after = $before;
            } elseif ($decision === 'CONFIRMED_COMPLETED') {
                if ($before === 'FAILED') {
                    throw ValidationException::withMessages([
                        'decision' => ['A failed dispense cannot be completed.'],
                    ]);
                }

                $after = $this->dispenseService
                    ->completeDispense($locked)->status;
            } elseif ($decision === 'CONFIRMED_FAILED') {
                if ($before === 'COMPLETED') {
                    throw ValidationException::withMessages([
                        'decision' => ['A completed dispense cannot be failed.'],
                    ]);
                }

                $after = $this->dispenseService
                    ->failDispense($locked)->status;
            } else {
                throw ValidationException::withMessages([
                    'decision' => ['Invalid reconciliation decision.'],
                ]);
            }

            return DispenseReconciliation::create([
                'dispense_id' => $locked->id,
                'user_id' => $user->id,
                'decision' => $decision,
                'reason' => $reason,
                'evidence' => $evidence,
                'status_before' => $before,
                'status_after' => $after,
            ]);
        });
    }
}