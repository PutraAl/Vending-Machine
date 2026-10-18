<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispensingController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));

        $statuses = Dispense::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $totalDispenses = Dispense::count();

        $activeDispenses = Dispense::whereIn('status', [
            'PENDING',
            'DISPENSING',
        ])->count();

        $completedDispenses = Dispense::where(
            'status',
            'COMPLETED'
        )->count();

        $failedDispenses = Dispense::where(
            'status',
            'FAILED'
        )->count();

        $query = Dispense::with([
            'order.items.product',
            'machine',
            'slot.product',
        ]);

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->whereHas('order', function (Builder $orderQuery) use ($search) {
                        $orderQuery->where(
                            'order_code',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('machine', function (Builder $machineQuery) use ($search) {
                        $machineQuery->where(
                            'machine_code',
                            'like',
                            "%{$search}%"
                        );
                    })
                    ->orWhereHas('slot', function (Builder $slotQuery) use ($search) {
                        $slotQuery
                            ->where('slot_code', 'like', "%{$search}%")
                            ->orWhereHas('product', function (Builder $productQuery) use ($search) {
                                $productQuery->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                            });
                    });

                if (ctype_digit($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        if ($status !== '' && $statuses->contains($status)) {
            $query->where('status', $status);
        }

        $dispenses = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('dispensing.index', compact(
            'dispenses',
            'statuses',
            'search',
            'status',
            'totalDispenses',
            'activeDispenses',
            'completedDispenses',
            'failedDispenses'
        ));
    }
}
