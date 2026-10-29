<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneOrphanedReceipts extends Command
{
    protected $signature = 'receipts:prune-orphans';

    protected $description = 'Elimina comprobantes privados antiguos que no pertenecen a ningún pedido';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(max(1, (int) config('services.receipts.orphan_retention_hours', 48)));
        $deletedCount = 0;
        $failedCount = 0;

        foreach (array_chunk($disk->allFiles('receipts'), 500) as $paths) {
            $referencedPaths = DB::table('orders')
                ->whereIn('receipt_path', $paths)
                ->pluck('receipt_path')
                ->all();

            foreach (array_diff($paths, $referencedPaths) as $path) {
                if ($disk->lastModified($path) > $cutoff->getTimestamp()) {
                    continue;
                }

                if ($disk->delete($path)) {
                    $deletedCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        Log::info('Pruned orphaned private receipts.', [
            'deleted_count' => $deletedCount,
            'failed_count' => $failedCount,
        ]);

        $this->info("Comprobantes huérfanos eliminados: {$deletedCount}.");

        if ($failedCount > 0) {
            $this->error("No se pudieron eliminar {$failedCount} archivos huérfanos.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
