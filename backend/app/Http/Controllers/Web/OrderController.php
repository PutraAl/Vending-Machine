<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));

        $statuses = Order::query()
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $query = Order::query()
            ->with([
                'machine',
                'items.product',
                'items.slot',
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('machine', function ($machineQuery) use ($search) {
                        $machineQuery->where(
                            'machine_code',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        if ($status !== '' && $statuses->contains($status)) {
            $query->where('status', $status);
        }

        $orders = $query
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('orders.index', compact(
            'orders',
            'statuses',
            'search',
            'status'
        ));
    }
}
