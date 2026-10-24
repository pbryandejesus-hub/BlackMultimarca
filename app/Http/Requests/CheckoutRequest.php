<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'cedula' => ['required', 'string', 'max:20', 'regex:/^[0-9.\-]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'delivery_method' => ['required', Rule::in([Order::DELIVERY_PICKUP, Order::DELIVERY_SHIPPING])],
            'address' => ['nullable', 'required_if:delivery_method,'.Order::DELIVERY_SHIPPING, 'string', 'max:255'],
            'city' => ['nullable', 'required_if:delivery_method,'.Order::DELIVERY_SHIPPING, 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_name' => 'nombre y apellido',
            'cedula' => 'cédula de identidad',
            'phone' => 'teléfono / WhatsApp',
            'delivery_method' => 'forma de entrega',
            'address' => 'dirección',
            'city' => 'ciudad',
            'receipt' => 'comprobante',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula de identidad solo puede tener números y puntos.',
            'address.required_if' => 'Ingresá la dirección para el envío.',
            'city.required_if' => 'Ingresá la ciudad para el envío.',
        ];
    }
}
