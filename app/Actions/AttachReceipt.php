<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Stores a private receipt and delegates the locked order transition.
 */
class AttachReceipt
{
    public function __construct(private UpdateOrderStatus $updateOrderStatus) {}

    public function handle(Order $order, UploadedFile $receipt, ?User $actor = null): void
    {
        $newPath = $receipt->store('receipts', 'local');

        if (! $newPath) {
            throw ValidationException::withMessages(['receipt' => 'No se pudo guardar el comprobante. Intentá nuevamente.']);
        }

        try {
            $previousPath = $this->updateOrderStatus->attachReceipt($order, $newPath, $actor);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newPath);
            throw $exception;
        }

        if ($previousPath && $previousPath !== $newPath) {
            Storage::disk('local')->delete($previousPath);
        }
    }
}
