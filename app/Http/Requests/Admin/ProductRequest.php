<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'alpha_dash', 'max:180', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'compare_price' => ['nullable', 'integer', 'gt:price', 'max:'.PHP_INT_MAX],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => $productId
                ? ['nullable', 'integer', 'distinct', Rule::exists('product_variants', 'id')->where('product_id', $productId)]
                : ['prohibited'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_featured' => $this->boolean('is_featured'),
            'price' => $this->normalizeAmount($this->input('price')),
            'compare_price' => $this->normalizeAmount($this->input('compare_price')),
        ]);
    }

    /**
     * Permite cargar precios como "150.000" o "150000".
     */
    private function normalizeAmount(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_replace('/[^0-9]/', '', (string) $value);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'categoría',
            'brand_id' => 'marca',
            'price' => 'precio',
            'compare_price' => 'precio anterior',
            'images.*' => 'imagen',
            'variants' => 'variantes',
            'variants.*.stock' => 'stock',
            'variants.*.color_hex' => 'código de color',
        ];
    }
}
