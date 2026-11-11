<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use Illuminate\Http\RedirectResponse;

class StockAlertController extends Controller
{
    /**
     * Marca que ya se le avisó al cliente que volvió el stock.
     */
    public function update(StockAlert $stockAlert): RedirectResponse
    {
        $stockAlert->update(['notified_at' => now()]);

        return back()->with('status', "Marcaste como avisado a {$stockAlert->contact}.");
    }

    public function destroy(StockAlert $stockAlert): RedirectResponse
    {
        $stockAlert->delete();

        return back()->with('status', "Quitaste a {$stockAlert->contact} de la lista de interesados.");
    }
}
