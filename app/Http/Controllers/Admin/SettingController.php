<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Setting::allValues()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->merge([
            'shipping_cost' => preg_replace('/[^0-9]/', '', (string) $request->input('shipping_cost')) ?: '0',
        ]);

        $validated = $request->validate([
            'store_whatsapp' => ['nullable', 'string', 'max:30'],
            'store_instagram' => ['nullable', 'string', 'max:60'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:255'],
            'store_hours' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_holder' => ['nullable', 'string', 'max:120'],
            'bank_holder_document' => ['nullable', 'string', 'max:30'],
            'bank_account' => ['nullable', 'string', 'max:60'],
            'bank_alias' => ['nullable', 'string', 'max:60'],
            'discount_min_items' => ['required', 'integer', 'min:0', 'max:100'],
            'discount_percent' => ['required', 'integer', 'min:0', 'max:90'],
            'shipping_cost' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'reserve_hours' => ['required', 'integer', 'min:1', 'max:720'],
        ]);

        Setting::store($validated);

        return back()->with('status', 'Configuración guardada.');
    }
}
