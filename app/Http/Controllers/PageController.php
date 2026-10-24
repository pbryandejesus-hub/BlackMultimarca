<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const PAGES = [
        'como-comprar' => 'Cómo comprar',
        'envios' => 'Envíos y retiros',
        'cambios' => 'Cambios y devoluciones',
        'preguntas-frecuentes' => 'Preguntas frecuentes',
    ];

    public function show(string $page): View
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        return view("pages.{$page}", [
            'title' => self::PAGES[$page],
            'settings' => Setting::allValues(),
        ]);
    }
}
