<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use App\Models\Machine;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();

        $totalMachines = Machine::count();

        $ordersToday = Order::whereDate('created_at', today())->count();

        $activeDispensing = Dispense::where('status', 'PENDING')->count();

        $machines = Machine::with('latestTelemetry')
            ->orderBy('machine_code')
            ->get();

        return view('dashboard', compact(
            'totalProducts',
            'totalMachines',
            'ordersToday',
            'activeDispensing',
            'machines'
        ));
    }
}
