<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dispense;
use App\Models\Machine;
use App\Models\MachineError;
use App\Models\MachineSlot;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;


class DashboardController extends Controller
{
    public function index()
    {
        $totalProducts = Product::count();

        $totalMachines = Machine::count();

        $ordersToday = Order::whereDate('created_at', today())->count();

        $activeDispensing = Dispense::where(
            'status',
            'DISPENSING'
        )->count();

        $machines = Machine::with([
            'latestTelemetry',
            'latestTemperatureTelemetry',
        ])->orderBy('machine_code')->get();

        // Slot dengan stok siap jual sebanyak 3 unit atau kurang.
        $lowStockSlots = MachineSlot::with(['machine', 'product'])
            ->whereRaw('(current_qty - hold_qty) <= ?', [3])
            ->orderByRaw('(current_qty - hold_qty) ASC')
            ->orderBy('slot_code')
            ->limit(5)
            ->get();

        // Ambil kejadian terbaru dari tabel yang sudah tersedia.
        $recentErrors = MachineError::with('machine')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentOrders = Order::with('machine')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentDispenses = Dispense::with(['machine', 'order'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        // Samakan format data agar dapat ditampilkan dalam satu daftar.
        $recentActivities = collect()
            ->concat($recentErrors->map(fn($error) => [
                'type' => 'error',
                'title' => 'Error mesin: ' . ($error->error_code ?: 'UNKNOWN'),
                'description' => ($error->machine?->machine_code ?? 'Mesin tidak diketahui')
                    . ($error->message ? ' · ' . $error->message : ''),
                'occurred_at' => $error->created_at,
            ]))
            ->concat($recentOrders->map(fn($order) => [
                'type' => 'order',
                'title' => 'Order ' . ($order->order_code ?? '#' . $order->id)
                    . ' · ' . strtoupper($order->status ?? 'UNKNOWN'),
                'description' => 'Mesin: '
                    . ($order->machine?->machine_code ?? 'Tidak diketahui'),
                'occurred_at' => $order->updated_at ?? $order->created_at,
            ]))
            ->concat($recentDispenses->map(fn($dispense) => [
                'type' => 'dispense',
                'title' => 'Dispense #' . $dispense->id
                    . ' · ' . strtoupper($dispense->status ?? 'UNKNOWN'),
                'description' => ($dispense->machine?->machine_code ?? 'Mesin tidak diketahui')
                    . ($dispense->order?->order_code
                        ? ' · Order ' . $dispense->order->order_code
                        : ''),
                'occurred_at' => $dispense->completed_at
                    ?? $dispense->started_at
                    ?? $dispense->updated_at
                    ?? $dispense->created_at,
            ]))
            ->sortByDesc(
                fn($activity) => $activity['occurred_at']?->getTimestamp() ?? 0
            )
            ->take(6)
            ->values();



        // Jika halaman dan query dashboard berhasil, backend dan database aktif.
        $backendHealth = 'ONLINE';
        $databaseHealth = 'ONLINE';

        // Periksa apakah endpoint TCP broker MQTT dapat dijangkau.
        // Hasil disimpan sebentar agar tidak menguji koneksi setiap page load.
        $mqttHost = trim((string) config('mqtt.host', ''));
        $mqttPort = (int) config('mqtt.port', 1883);

        $mqttHealth = Cache::remember(
            'dashboard.mqtt_tcp_health',
            now()->addSeconds(15),
            function () use ($mqttHost, $mqttPort): string {
                if ($mqttHost === '' || $mqttPort < 1) {
                    return 'NOT CONFIGURED';
                }

                $errorCode = 0;
                $errorMessage = '';

                $socket = @fsockopen(
                    $mqttHost,
                    $mqttPort,
                    $errorCode,
                    $errorMessage,
                    1.0
                );

                if (! is_resource($socket)) {
                    return 'UNREACHABLE';
                }

                fclose($socket);

                return 'TCP REACHABLE';
            }
        );

        $systemStatus = match ($mqttHealth) {
            'TCP REACHABLE' => 'MQTT ENDPOINT REACHABLE',
            'NOT CONFIGURED' => 'MQTT CONFIGURATION REQUIRED',
            default => 'MQTT ENDPOINT UNREACHABLE',
        };

        $systemStatusClass = match ($mqttHealth) {
            'TCP REACHABLE' => 'text-emerald-300',
            'NOT CONFIGURED' => 'text-amber-300',
            default => 'text-rose-300',
        };

        return view('dashboard', compact(
            'totalProducts',
            'totalMachines',
            'ordersToday',
            'activeDispensing',
            'machines',
            'lowStockSlots',
            'recentActivities',
            'backendHealth',
            'databaseHealth',
            'mqttHealth',
            'systemStatus',
            'systemStatusClass',

        ));
    }
}
