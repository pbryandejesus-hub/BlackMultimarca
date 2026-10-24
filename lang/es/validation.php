<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mensajes de validación
    |--------------------------------------------------------------------------
    */

    'accepted' => 'Tenés que aceptar :attribute.',
    'alpha_dash' => ':Attribute solo puede tener letras, números, guiones y guiones bajos.',
    'array' => ':Attribute no es válido.',
    'boolean' => ':Attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'email' => 'Ingresá un correo electrónico válido.',
    'enum' => 'El valor elegido para :attribute no es válido.',
    'exists' => 'El valor elegido para :attribute no es válido.',
    'file' => ':Attribute debe ser un archivo.',
    'gt' => [
        'numeric' => ':Attribute debe ser mayor que :value.',
    ],
    'image' => ':Attribute debe ser una imagen.',
    'in' => 'El valor elegido para :attribute no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'mimes' => ':Attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Completá :attribute.',
    'required_if' => 'Completá :attribute.',
    'string' => ':Attribute debe ser un texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'uploaded' => 'No se pudo subir :attribute.',

    'password' => [
        'letters' => ':Attribute debe tener al menos una letra.',
        'mixed' => ':Attribute debe tener mayúsculas y minúsculas.',
        'numbers' => ':Attribute debe tener al menos un número.',
        'symbols' => ':Attribute debe tener al menos un símbolo.',
        'uncompromised' => 'Esa contraseña apareció en una filtración de datos. Elegí otra.',
    ],

    'custom' => [],

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'phone' => 'teléfono',
        'quantity' => 'cantidad',
        'description' => 'descripción',
        'slug' => 'URL',
        'status' => 'estado',
    ],

];
