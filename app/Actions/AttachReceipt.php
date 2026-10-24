<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Guarda el comprobante de transferencia (en disco privado) y pasa el pedido a revisión.
 */
class AttachReceipt
{
    public function handle(Order $order, UploadedFile $receipt): void
    {
        $previousPath = $order->receipt_path;

        $order->update([
            'receipt_path' => $receipt->store('receipts', 'local'),
            'receipt_uploaded_at' => now(),
            'status' => OrderStatus::UnderReview,
        ]);

        if ($previousPath) {
            Storage::disk('local')->delete($previousPath);
        }
    }
}
