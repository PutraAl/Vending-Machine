<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\MachineError;
use Illuminate\Database\Seeder;

class MachineErrorSeeder extends Seeder
{
    public function run(): void
    {
        // Gunakan mesin yang sudah terdaftar.
        $machine = Machine::query()
            ->orderBy('id')
            ->first();

        if (! $machine) {
            $this->command?->warn(
                'Seeder dibatalkan: tambahkan mesin terlebih dahulu.'
            );

            return;
        }

        $now = now();

        $sampleErrors = [
            [
                'error_code' => 'TEMP_HIGH',
                'message' => 'Suhu mesin melewati batas aman yang dikonfigurasi.',
                'severity' => 'CRITICAL',
                'created_minutes_ago' => 15,
                'resolved_minutes_ago' => null,
            ],
            [
                'error_code' => 'DISPENSE_FAILED',
                'message' => 'Produk gagal keluar setelah perintah dispense.',
                'severity' => 'HIGH',
                'created_minutes_ago' => 45,
                'resolved_minutes_ago' => null,
            ],
            [
                'error_code' => 'DISPENSE_OUTCOME_UNKNOWN',
                'message' => 'Hasil dispense belum pasti. Perlu pemeriksaan dan rekonsiliasi.',
                'severity' => 'WARNING',
                'created_minutes_ago' => 70,
                'resolved_minutes_ago' => null,
            ],
            [
                'error_code' => 'MQTT_CONNECTION_LOST',
                'message' => 'Koneksi ke broker MQTT sempat terputus.',
                'severity' => 'INFO',
                'created_minutes_ago' => 180,
                'resolved_minutes_ago' => 150,
            ],
        ];

        foreach ($sampleErrors as $sample) {
            MachineError::firstOrCreate(
                [
                    'machine_id' => $machine->id,
                    'error_code' => $sample['error_code'],
                    'message' => $sample['message'],
                ],
                [
                    'severity' => $sample['severity'],

                    // Model MachineError tidak memakai timestamps otomatis.
                    'created_at' => $now->copy()->subMinutes(
                        $sample['created_minutes_ago']
                    ),

                    'resolved_at' => $sample['resolved_minutes_ago'] === null
                        ? null
                        : $now->copy()->subMinutes(
                            $sample['resolved_minutes_ago']
                        ),
                ]
            );
        }

        $this->command?->info(
            "Data contoh Machine Error sudah diperiksa untuk mesin {$machine->machine_code}."
        );
    }
}
