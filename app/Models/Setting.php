<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración editable de la tienda: datos bancarios, contacto, descuento por cantidad y envío.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    private const CACHE_KEY = 'store_settings';

    /**
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'store_whatsapp' => '',
        'store_instagram' => '',
        'store_email' => '',
        'store_address' => '',
        'store_hours' => '',
        'bank_name' => '',
        'bank_holder' => '',
        'bank_holder_document' => '',
        'bank_account' => '',
        'bank_alias' => '',
        'discount_min_items' => '3',
        'discount_percent' => '10',
        'shipping_cost' => '0',
        'reserve_hours' => '48',
    ];

    /**
     * @return array<string, string>
     */
    public static function allValues(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array => static::query()->pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, array_filter($stored, fn (?string $value): bool => $value !== null));
    }

    public static function get(string $key): string
    {
        return static::allValues()[$key] ?? '';
    }

    public static function integer(string $key): int
    {
        return (int) static::get($key);
    }

    /**
     * @param  array<string, string|int|null>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, self::DEFAULTS)) {
                static::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
            }
        }

        Cache::forget(self::CACHE_KEY);
    }
}
