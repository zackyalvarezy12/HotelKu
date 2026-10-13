<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SecurePaymentProofs extends Command
{
    protected $signature = 'payments:secure-proofs';

    protected $description = 'Move public payment proofs to private storage';

    public function handle(): int
    {
        $publicDisk = Storage::disk('public');
        $privateDisk = Storage::disk('local');
        $migrated = 0;
        $failed = 0;

        Payment::query()
            ->whereNotNull('proof_path')
            ->orderBy('id')
            ->chunkById(100, function ($payments) use ($publicDisk, $privateDisk, &$migrated, &$failed): void {
                foreach ($payments as $payment) {
                    $path = $payment->proof_path;

                    if (! str_starts_with($path, 'payment-proofs/')) {
                        $this->warn("Skipped unexpected proof path for payment {$payment->id}.");
                        $failed++;
                        continue;
                    }

                    if (! $publicDisk->exists($path)) {
                        if ($privateDisk->exists($path)) {
                            continue;
                        }

                        $this->warn("Missing proof file for payment {$payment->id}.");
                        $failed++;
                        continue;
                    }

                    $contents = $publicDisk->get($path);

                    if ($privateDisk->exists($path)) {
                        if (! hash_equals(hash('sha256', $privateDisk->get($path)), hash('sha256', $contents))) {
                            $this->error("Private proof differs for payment {$payment->id}; public copy was kept.");
                            $failed++;
                            continue;
                        }
                    } elseif (! $privateDisk->put($path, $contents)
                        || ! $privateDisk->exists($path)
                        || ! hash_equals(hash('sha256', $privateDisk->get($path)), hash('sha256', $contents))) {
                        $privateDisk->delete($path);
                        $this->error("Could not verify private copy for payment {$payment->id}; public copy was kept.");
                        $failed++;
                        continue;
                    }

                    if (! $publicDisk->delete($path)) {
                        $this->error("Could not remove public copy for payment {$payment->id}.");
                        $failed++;
                        continue;
                    }

                    $migrated++;
                }
            });

        $this->info("Secured {$migrated} payment proof(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}